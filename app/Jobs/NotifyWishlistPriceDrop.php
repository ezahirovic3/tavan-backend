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
 * Fans out a price-drop push + in-app notification to everyone who has this
 * product wishlisted. Dispatched from App\Observers\ProductObserver — never
 * call this directly, the threshold/cooldown gating lives there.
 */
class NotifyWishlistPriceDrop implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $productId,
        private readonly float $oldPrice,
        private readonly float $newPrice,
    ) {}

    public function handle(PushNotificationService $push): void
    {
        $product = Product::find($this->productId);

        // Re-check status at send time (queued — the product may have sold
        // or been taken down between the price edit and this job running).
        if (! $product || $product->status !== 'active') {
            return;
        }

        $title = 'Cijena je pala 🔻';
        $body  = '"' . $product->title . '" sada košta ' . number_format($this->newPrice, 2)
            . ' KM (bilo ' . number_format($this->oldPrice, 2) . ' KM).';
        $meta = [
            'productId' => $product->id,
            'oldPrice'  => $this->oldPrice,
            'newPrice'  => $this->newPrice,
        ];

        WishlistItem::where('product_id', $product->id)
            ->select(['id', 'user_id'])
            ->chunkById(500, function ($items) use ($push, $title, $body, $meta) {
                $push->sendToUsers($items->pluck('user_id')->all(), $title, $body, [
                    'type' => 'price_drop',
                    ...$meta,
                ]);
            });

        WishlistItem::where('product_id', $product->id)
            ->with('user')
            ->chunkById(500, function ($items) use ($title, $body, $meta) {
                $users = $items->pluck('user')->filter();

                if ($users->isNotEmpty()) {
                    Notification::send($users, new UserActivityNotification('price_drop', $title, $body, $meta));
                }
            });
    }
}
