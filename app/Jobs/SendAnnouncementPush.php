<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendAnnouncementPush implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Announcement $announcement) {}

    public function handle(PushNotificationService $push): void
    {
        $this->announcement
            ->targetedUsersQuery()
            ->select('id')
            ->chunkById(500, function ($users) use ($push) {
                $push->sendToUsers(
                    $users->pluck('id')->all(),
                    'Tavan',
                    $this->announcement->title,
                    ['type' => 'announcement', 'announcementId' => $this->announcement->id],
                );
            });
    }
}
