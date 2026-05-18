<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

class ImageService
{
    private const DISK = 'r2';

    /** Maximum pixels on the longest edge for product / message images. */
    private const MAX_PRODUCT_EDGE = 1600;

    /** Maximum pixels on the longest edge for avatars (square-ish, smaller cap). */
    private const MAX_AVATAR_EDGE = 800;

    /** JPEG encode quality (0–100). 82 is visually lossless on mobile screens. */
    private const JPEG_QUALITY = 82;

    private const MAX_PRODUCT_IMAGES = 10;

    // ── Upload helpers ─────────────────────────────────────────────────────────

    public function uploadProductImage(Product $product, UploadedFile $file): ProductImage
    {
        abort_if(
            $product->images()->count() >= self::MAX_PRODUCT_IMAGES,
            422,
            'Maksimalan broj slika po proizvodu je ' . self::MAX_PRODUCT_IMAGES . '.'
        );

        $path      = $this->store("products/{$product->id}", $file, self::MAX_PRODUCT_EDGE);
        $nextOrder = $product->images()->max('sort_order') + 1;

        return $product->images()->create([
            'url'        => $this->publicUrl($path),
            'sort_order' => $nextOrder,
        ]);
    }

    public function deleteProductImage(ProductImage $image): void
    {
        $this->deleteByUrl($image->url);
        $image->delete();
    }

    public function reorderProductImages(Product $product, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            $product->images()->where('id', $id)->update(['sort_order' => $index]);
        }
    }

    public function uploadMessageImage(UploadedFile $file): string
    {
        $path = $this->store('messages', $file, self::MAX_PRODUCT_EDGE);

        return $this->publicUrl($path);
    }

    public function uploadAvatar(User $user, UploadedFile $file): string
    {
        // Delete old avatar from R2 before storing the new one
        if ($user->avatar) {
            $this->deleteByUrl($user->avatar);
        }

        $path = $this->store("avatars/{$user->id}", $file, self::MAX_AVATAR_EDGE);
        $url  = $this->publicUrl($path);

        $user->update(['avatar' => $url]);

        return $url;
    }

    // ── Public delete helper (used by models + controllers) ───────────────────

    /**
     * Delete a file from R2 by its full public URL.
     * Safe to call with null or non-R2 URLs — silently ignored.
     */
    public function deleteByUrl(?string $url): void
    {
        if (! $url) return;

        $base = rtrim(config('filesystems.disks.r2.url'), '/') . '/';

        if (str_starts_with($url, $base)) {
            $path = substr($url, strlen($base));
            Storage::disk(self::DISK)->delete($path);
        }
    }

    // ── Private storage pipeline ───────────────────────────────────────────────

    /**
     * Process, compress, resize and store an uploaded image to R2.
     * Always outputs JPEG regardless of input format (including HEIC/HEIF).
     *
     * @param string       $directory  R2 path prefix, e.g. "products/01J..."
     * @param UploadedFile $file       Raw uploaded file
     * @param int          $maxEdge    Maximum pixels on the longest edge
     * @return string                  R2 object path (no leading slash)
     */
    private function store(string $directory, UploadedFile $file, int $maxEdge): string
    {
        $filename = Str::uuid() . '.jpg';
        $path     = "{$directory}/{$filename}";

        $manager = new ImageManager(new GdDriver());
        $image   = $manager->read($file->getPathname());

        // Scale down only — never upscale smaller images
        if ($image->width() > $maxEdge || $image->height() > $maxEdge) {
            $image->scaleDown($maxEdge, $maxEdge);
        }

        $jpeg = $image->toJpeg(self::JPEG_QUALITY);

        Storage::disk(self::DISK)->put($path, (string) $jpeg, 'public');

        return $path;
    }

    private function publicUrl(string $path): string
    {
        return rtrim(config('filesystems.disks.r2.url'), '/') . '/' . $path;
    }
}
