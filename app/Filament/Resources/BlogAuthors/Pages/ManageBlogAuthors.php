<?php

namespace App\Filament\Resources\BlogAuthors\Pages;

use App\Filament\Resources\BlogAuthors\BlogAuthorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBlogAuthors extends ManageRecords
{
    protected static string $resource = BlogAuthorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novi autor')
                ->slideOver()
                ->icon('heroicon-m-plus'),
        ];
    }
}
