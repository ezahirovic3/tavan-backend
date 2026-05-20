<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Services\ImageService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected array $syncImagePaths = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->visible(fn () => auth()->user()->isSuperAdmin()),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $base = rtrim(config('filesystems.disks.r2.url'), '/') . '/';

        $data['images'] = $this->record->images
            ->map(fn ($img) => str_starts_with($img->url, $base)
                ? substr($img->url, strlen($base))
                : $img->url
            )
            ->values()
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->syncImagePaths = $data['images'] ?? [];
        unset($data['images']);

        return $data;
    }

    protected function afterSave(): void
    {
        $base = rtrim(config('filesystems.disks.r2.url'), '/') . '/';
        $record = $this->record;

        $newUrls = collect($this->syncImagePaths)
            ->map(fn ($path) => str_starts_with($path, 'http') ? $path : $base . $path)
            ->values()
            ->all();

        $existingImages = $record->images()->get();
        $existingUrls   = $existingImages->pluck('url')->all();

        $imageService = app(ImageService::class);

        foreach ($existingImages as $img) {
            if (! in_array($img->url, $newUrls)) {
                $imageService->deleteProductImage($img);
            }
        }

        foreach ($newUrls as $index => $url) {
            if (! in_array($url, $existingUrls)) {
                $record->images()->create(['url' => $url, 'sort_order' => $index]);
            } else {
                $record->images()->where('url', $url)->update(['sort_order' => $index]);
            }
        }
    }
}
