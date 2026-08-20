<?php

namespace App\Jobs;

use App\Models\Follow;
use App\Models\Product;
use App\Models\User;
use App\Notifications\UserActivityNotification;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

/**
 * Fans out a "new listing(s)" push + in-app notification to a seller's
 * followers. Dispatched from App\Console\Commands\NotifyFollowersOfNewListingsCommand,
 * which owns the 30-min-after-last-listing batching/debounce logic — never
 * call this directly. $productIds is the batch of listings the command
 * already stamped `followers_notified_at` on.
 */
class NotifyFollowersNewListings implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string[]  $productIds
     */
    public function __construct(
        private readonly string $sellerId,
        private readonly array $productIds,
    ) {}

    public function handle(PushNotificationService $push): void
    {
        $seller = User::find($this->sellerId);

        if (! $seller || empty($this->productIds)) {
            return;
        }

        $count = count($this->productIds);

        if ($count === 1) {
            $product = Product::find($this->productIds[0]);
            $title   = 'Novi artikal 🆕';
            $body    = ($product ? '"' . $product->title . '" — ' : '') . $seller->name . ' je upravo objavio/la novi artikal.';
            $meta    = array_filter(['productId' => $product?->id, 'sellerId' => $seller->id]);
        } else {
            $title = 'Novi artikli 🆕';
            $body  = $seller->name . ' je objavio/la ' . $count . ' novih artikala.';
            $meta  = ['sellerId' => $seller->id, 'count' => $count];
        }

        Follow::where('followed_id', $seller->id)
            ->select(['id', 'follower_id'])
            ->chunkById(500, function ($follows) use ($push, $title, $body, $meta) {
                $push->sendToUsers($follows->pluck('follower_id')->all(), $title, $body, [
                    'type' => 'new_listing',
                    ...$meta,
                ]);
            });

        Follow::where('followed_id', $seller->id)
            ->with('follower')
            ->chunkById(500, function ($follows) use ($title, $body, $meta) {
                $users = $follows->pluck('follower')->filter();

                if ($users->isNotEmpty()) {
                    Notification::send($users, new UserActivityNotification('new_listing', $title, $body, $meta));
                }
            });
    }
}
