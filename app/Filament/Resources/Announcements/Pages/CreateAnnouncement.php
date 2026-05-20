<?php
namespace App\Filament\Resources\Announcements\Pages;
use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Jobs\SendAnnouncementPush;
use Filament\Resources\Pages\CreateRecord;
class CreateAnnouncement extends CreateRecord {
    protected static string $resource = AnnouncementResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array {
        $data['created_by'] = auth()->id();
        $data['sent_at'] = now();
        return $data;
    }
    protected function afterCreate(): void {
        SendAnnouncementPush::dispatch($this->record);
    }
}
