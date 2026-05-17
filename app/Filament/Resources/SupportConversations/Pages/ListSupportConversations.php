<?php

namespace App\Filament\Resources\SupportConversations\Pages;

use App\Filament\Resources\SupportConversations\SupportConversationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupportConversations extends ListRecords
{
    protected static string $resource = SupportConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novi razgovor')
                ->icon('heroicon-m-plus'),
        ];
    }
}
