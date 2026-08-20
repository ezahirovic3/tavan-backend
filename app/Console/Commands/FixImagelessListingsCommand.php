<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\PushNotificationService;
use App\Services\UserNotificationService;
use Illuminate\Console\Command;

/**
 * Sweeps listings caught by the bug where the mobile app's "publish now"
 * flow (App\Http\Controllers\Api\ProductController::store) could go
 * straight to 'active'/'pending_review' before photos were uploaded, and a
 * failed photo upload left the listing live with zero images (fixed
 * client-side by routing that path through the create-draft → upload →
 * publish sequence, which is guarded — see ProductController::publish).
 *
 * Demotes any surviving/newly-recurring case back to 'draft' — dropping it
 * out of the public feed — and nudges the seller (push + in-app) to add
 * photos and republish. Nothing is deleted. Scheduled daily (routes/console.php)
 * as an ongoing safety net, not just a one-off backfill.
 */
class FixImagelessListingsCommand extends Command
{
    protected $signature = 'products:fix-imageless-listings {--dry-run : List affected products without changing anything or notifying anyone}';

    protected $description = 'Demote active/pending_review listings that have zero images back to draft, and nudge the seller';

    public function handle(PushNotificationService $push, UserNotificationService $notifications): int
    {
        $affected = Product::whereIn('status', ['active', 'pending_review'])
            ->doesntHave('images')
            ->with('seller')
            ->get(['id', 'title', 'seller_id', 'status']);

        if ($affected->isEmpty()) {
            $this->info('No imageless listings found.');

            return self::SUCCESS;
        }

        $this->table(
            ['id', 'title', 'seller_id', 'status'],
            $affected->map(fn (Product $p) => [$p->id, $p->title, $p->seller_id, $p->status])->all()
        );

        if ($this->option('dry-run')) {
            $this->info("{$affected->count()} listing(s) would be demoted to draft. Re-run without --dry-run to apply.");

            return self::SUCCESS;
        }

        foreach ($affected as $product) {
            $product->update(['status' => 'draft']);

            if (! $product->seller) {
                continue;
            }

            $title = 'Oglas treba fotografije 📸';
            $body  = 'Tvoj oglas "' . $product->title . '" je vraćen u nacrte jer nema nijednu fotografiju. Dodaj slike i objavi ga ponovo.';
            $meta  = ['productId' => $product->id];

            $push->sendToUser($product->seller_id, $title, $body, [
                'type' => 'listing_needs_photos',
                ...$meta,
            ]);

            $notifications->record($product->seller, 'listing_needs_photos', $title, $body, $meta);
        }

        $this->info("{$affected->count()} listing(s) demoted to draft and their sellers notified.");

        return self::SUCCESS;
    }
}
