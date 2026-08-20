<?php

namespace App\Console\Commands;

use App\Jobs\NotifyFollowersNewListings;
use App\Models\Follow;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Batches "new listing" notifications so a seller listing several items in
 * a row produces one notification to their followers, not one per listing.
 *
 * A product's `followers_notified_at` stays null until it's been included in
 * a fan-out. Grouping unnotified active products by seller and looking at
 * the *most recent* one in each group means a seller's batch only "closes"
 * once `follow_notification_quiet_minutes` has passed since their last
 * listing — every new listing pushes the window back, for free, no
 * cache/reschedule bookkeeping needed. Scheduled every 5 minutes
 * (routes/console.php).
 */
class NotifyFollowersOfNewListingsCommand extends Command
{
    protected $signature = 'follows:notify-new-listings';

    protected $description = "Fan out a batched 'new listing' notification to a seller's followers, ~N minutes after their last listing";

    public function handle(): int
    {
        $quietMinutes = (int) config('tavan.follow_notification_quiet_minutes');
        $cutoff       = now()->subMinutes($quietMinutes);

        $pending = Product::where('status', 'active')
            ->whereNull('followers_notified_at')
            ->select(['id', 'seller_id', 'created_at'])
            ->get()
            ->groupBy('seller_id');

        $notified = 0;

        foreach ($pending as $sellerId => $products) {
            $latest = $products->max('created_at');

            if ($latest->gt($cutoff)) {
                continue; // still might get more listings before the window closes
            }

            $productIds = $products->pluck('id')->all();

            // Mark first — a mass update on a column ProductObserver doesn't hook
            // on (not status/price), so this can't trigger unrelated side effects.
            // Goes through the base query builder, not Product::whereIn(...)->update() —
            // Eloquent's Builder::update() auto-injects updated_at on every mass update,
            // which would silently bump this listing to the top of the updated_at-sorted
            // feed (see ProductController::index) with no real edit behind it.
            DB::table('products')->whereIn('id', $productIds)->update(['followers_notified_at' => now()]);

            if (Follow::where('followed_id', $sellerId)->exists()) {
                NotifyFollowersNewListings::dispatch($sellerId, $productIds);
                $notified++;
            }
        }

        $this->info("{$notified} seller(s) with followers notified. " . $pending->count() . ' seller(s) had pending unnotified listings.');

        return self::SUCCESS;
    }
}
