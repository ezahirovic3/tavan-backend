<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\UserActivityNotification;

/**
 * Writes to the in-app notifications list (bell icon feed). Always persists,
 * independent of the user's push preferences (PushNotificationService is
 * the one that checks those) — see App\Support\NotificationCategory for why.
 */
class UserNotificationService
{
    /**
     * @param  string  $type  one of App\Support\NotificationCategory::LIST_TYPES
     * @param  array<string, mixed>  $meta
     */
    public function record(User $user, string $type, string $title, string $body, array $meta = []): void
    {
        $user->notify(new UserActivityNotification($type, $title, $body, $meta));
    }
}
