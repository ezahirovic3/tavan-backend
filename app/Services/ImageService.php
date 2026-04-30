<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maestroerror\HeicToJpg;

class ImageService
{
    private const DISK = 'r2';
    private const MAX_PRODUCT_IMAGES = 10;

    public function uploadProductImage(Product $product, UploadedFile $file): ProductImage
    {
        abort_if(
            $product->images()->count() >= self::MAX_PRODUCT_IMAGES,
            422,
            'Maksimalan broj slika po proizvodu je '.self::MAX_PRODUCT_IMAGES.'.'
        );

        $path = $this->store("products/{$product->id}", $file);
        $nextOrder = $product->images()->max('sort_order') + 1;

        return $product->images()->create([
            'url'        => $this->publicUrl($path),
            'sort_order' => $nextOrder,
        ]);
    }

    public function deleteProductImage(ProductImage $image): void
    {
        $this->delete($image->url);
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
        $path = $this->store('messages', $file);

        return $this->publicUrl($path);
    }

    public function uploadAvatar(User $user, UploadedFile $file): string
    {
        // Delete old avatar if it's an R2 file
        if ($user->avatar) {
            $this->delete($user->avatar);
        }

        $path = $this->store("avatars/{$user->id}", $file);
        $url  = $this->publicUrl($path);

        $user->update(['avatar' => $url]);

        return $url;
    }

    private function store(string $directory, UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if (in_array($ext, ['heic', 'heif'])) {
            $filename = Str::uuid().'.jpg';
            $jpeg     = HeicToJpg::convert($file->getPathname())->get();
            Storage::disk(self::DISK)->put("{$directory}/{$filename}", $jpeg, 'public');
            return "{$directory}/{$filename}";
        }

        $filename = Str::uuid().'.'.$ext;
        return Storage::disk(self::DISK)->putFileAs($directory, $file, $filename, 'public');
    }

    private function delete(string $url): void
    {
        $base = rtrim(config('filesystems.disks.r2.url'), '/').'/';

        if (str_starts_with($url, $base)) {
            $path = substr($url, strlen($base));
            Storage::disk(self::DISK)->delete($path);
        }
    }

    private function publicUrl(string $path): string
    {
        return rtrim(config('filesystems.disks.r2.url'), '/').'/'.$path;
    }
}
