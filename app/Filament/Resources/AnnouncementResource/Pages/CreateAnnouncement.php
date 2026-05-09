<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Resources\AnnouncementResource;
use App\Jobs\SendAnnouncementPush;
use App\Models\Announcement;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['sent_at']    = now();

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Announcement $announcement */
        $announcement = $this->record;

        // Dispatch bulk push in the background — avoids request timeout for large audiences
        SendAnnouncementPush::dispatch($announcement);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
