<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\WishlistItem;
use App\Notifications\UserActivityNotification;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

/**
 * Fans out a "sold" push + in-app notification to everyone who has this
 * product wishlisted. Dispatched from App\Observers\ProductObserver when a
 * product transitions to status 'sold' — never call this directly.
 *
 * Shares the 'price_drops' opt-out category with NotifyWishlistPriceDrop
 * (Settings → Notifikacije shows both under "Lista želja") since both are
 * wishlist-triggered events.
 */
class NotifyWishlistItemSold implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $productId,
    ) {}

    public function handle(PushNotificationService $push): void
    {
        $product = Product::find($this->productId);

        // Re-check at send time (queued) — nothing to notify about if the
        // product record is gone or somehow flipped off 'sold' again.
        if (! $product || $product->status !== 'sold') {
            return;
        }

        $title = 'Prodano 😔';
        $body  = '"' . $product->title . '" sa tvoje liste želja je upravo prodano. Pogledaj slične artikle.';
        $meta  = ['productId' => $product->id];

        WishlistItem::where('product_id', $product->id)
            ->select(['id', 'user_id'])
            ->chunkById(500, function ($items) use ($push, $title, $body, $meta) {
                $push->sendToUsers($items->pluck('user_id')->all(), $title, $body, [
                    'type' => 'wishlist_item_sold',
                    ...$meta,
                ]);
            });

        WishlistItem::where('product_id', $product->id)
            ->with('user')
            ->chunkById(500, function ($items) use ($title, $body, $meta) {
                $users = $items->pluck('user')->filter();

                if ($users->isNotEmpty()) {
                    Notification::send($users, new UserActivityNotification('wishlist_item_sold', $title, $body, $meta));
                }
            });
    }
}
