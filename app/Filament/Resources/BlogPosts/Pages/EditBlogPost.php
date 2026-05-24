<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Filament\Resources\BlogPosts\Concerns\TransformsBlocks;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlogPost extends EditRecord
{
    use TransformsBlocks;

    protected static string $resource = BlogPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->visible(fn () => auth()->user()->isSuperAdmin()),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->blocksFlatToNested($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->blocksNestedToFlat($data);
    }
}
