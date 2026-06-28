<?php

namespace App\Services;

use App\Models\PushToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private const EXPO_API = 'https://exp.host/--/api/v2/push/send';

    /**
     * Send a push notification to all registered devices for a user.
     */
    public function sendToUser(string $userId, string $title, string $body, array $data = []): void
    {
        $tokens = PushToken::where('user_id', $userId)->pluck('token')->all();

        if (empty($tokens)) {
            return;
        }

        $badge = $this->incrementBadge($userId);

        $threadId  = isset($data['conversationId']) ? (string) $data['conversationId'] : null;
        $channelId = $threadId ? 'messages' : 'default';

        $messages = array_map(fn (string $token) => array_filter([
            'to'        => $token,
            'title'     => $title,
            'body'      => $body,
            'data'      => $data,
            'sound'     => 'default',
            'badge'     => $badge,
            'threadId'  => $threadId,
            'channelId' => $channelId,
        ]), $tokens);

        $this->dispatch($messages);
    }

    /**
     * Send a push notification to multiple users at once.
     *
     * @param  string[]  $userIds
     */
    public function sendToUsers(array $userIds, string $title, string $body, array $data = []): void
    {
        if (empty($userIds)) {
            return;
        }

        $tokens = PushToken::whereIn('user_id', $userIds)->pluck('token')->all();

        if (empty($tokens)) {
            return;
        }

        $messages = array_map(fn (string $token) => [
            'to'    => $token,
            'title' => $title,
            'body'  => $body,
            'data'  => $data,
            'sound' => 'default',
        ], $tokens);

        $this->dispatch($messages);
    }

    /**
     * Send a push notification to a filtered subset of all users.
     *
     * Supported filters:
     *   active_within_days  int|null  — only users active within N days
     *   has_listings        bool      — only users with at least one active listing
     *   root_category       string    — only users whose preferences include 'women'|'men'
     *
     * Returns the number of devices the push was sent to.
     */
    public function sendToFiltered(string $title, string $body, array $data = [], array $filters = []): int
    {
        $query = PushToken::query();

        if (! empty($filters['active_within_days'])) {
            $query->whereHas('user', fn ($q) =>
                $q->where('last_active_at', '>=', now()->subDays((int) $filters['active_within_days']))
            );
        }

        if (! empty($filters['has_listings'])) {
            $query->whereHas('user.products', fn ($q) =>
                $q->where('status', 'active')
            );
        }

        if (! empty($filters['root_category'])) {
            $query->whereHas('user.preferences', fn ($q) =>
                $q->whereJsonContains('categories', $filters['root_category'])
            );
        }

        $tokens = $query->pluck('token')->all();

        if (empty($tokens)) {
            return 0;
        }

        $messages = array_map(fn (string $token) => [
            'to'    => $token,
            'title' => $title,
            'body'  => $body,
            'data'  => $data,
            'sound' => 'default',
        ], $tokens);

        $this->dispatch($messages);

        return count($tokens);
    }

    /**
     * Atomically increment the push badge counter for a user and return the new value.
     * The counter is stored in Redis and reset to 0 when the user opens the app.
     */
    private function incrementBadge(string $userId): int
    {
        $key = "push_badge:{$userId}";
        Cache::add($key, 0, now()->addDays(30));
        return (int) Cache::increment($key);
    }

    /**
     * Reset the push badge counter for a user to 0 (called when the app is opened).
     */
    public function resetBadge(string $userId): void
    {
        Cache::forget("push_badge:{$userId}");
    }

    /**
     * POST messages to the Expo push endpoint in chunks of 100.
     */
    private function dispatch(array $messages): void
    {
        foreach (array_chunk($messages, 100) as $chunk) {
            try {
                $response = Http::withHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ])->post(self::EXPO_API, $chunk);

                if ($response->failed()) {
                    Log::warning('Expo push failed', [
                        'status' => $response->status(),
                        'body'   => $response->body(),
                    ]);
                }

                // Remove tickets with DeviceNotRegistered errors
                $this->pruneInvalidTokens($response->json('data', []));
            } catch (\Throwable $e) {
                Log::error('Expo push exception: ' . $e->getMessage());
            }
        }
    }

    /**
     * Delete tokens that Expo reports as invalid/unregistered.
     */
    private function pruneInvalidTokens(array $tickets): void
    {
        foreach ($tickets as $ticket) {
            if (
                isset($ticket['status'], $ticket['details']['error']) &&
                $ticket['status'] === 'error' &&
                $ticket['details']['error'] === 'DeviceNotRegistered'
            ) {
                // The token is embedded in the expoPushToken field of the receipt
                // but is not returned in the send ticket. We can't do per-ticket
                // pruning here; pruning happens when receipts are checked.
                // For simplicity, log it and let the mobile app re-register.
                Log::info('Expo: DeviceNotRegistered ticket received');
            }
        }
    }
}
