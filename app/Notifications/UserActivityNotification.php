<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Backs the notifications list (bell icon feed) — one generic, queueable
 * notification class for every event type in App\Support\NotificationCategory::LIST_TYPES,
 * rather than a bespoke class per event. Always persisted via the `database`
 * channel regardless of the recipient's push preferences — muting a category
 * only suppresses the push (see PushNotificationService), the in-app list
 * stays a complete record.
 *
 * Dispatched via App\Services\UserNotificationService::record(), not
 * directly — go through that service.
 */
class UserActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string  $type   one of App\Support\NotificationCategory::LIST_TYPES
     * @param  array<string, mixed>  $meta  event-specific payload (orderId, productId, status, old/new price, …)
     */
    public function __construct(
        private readonly string $type,
        private readonly string $title,
        private readonly string $body,
        private readonly array $meta = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'meta' => $this->meta,
        ];
    }
}
