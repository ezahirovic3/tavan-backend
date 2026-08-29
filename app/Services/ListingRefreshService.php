<?php

namespace App\Services;

use App\Exceptions\Refresh\RefreshNotEligibleException;
use App\Exceptions\Refresh\RefreshTooRecentException;
use App\Models\ListingRefresh;
use App\Models\Product;
use App\Models\User;
use App\Support\RefreshEligibility;
use Illuminate\Support\Facades\DB;

/**
 * "Osvježi oglas" — the free renewal for stale listings.
 *
 * Every rule lives here, and both ProductController::refresh() and
 * ProductResource go through eligibilityFor(), so the client's button state
 * and the server's answer are the same computation.
 *
 * This is NOT the future paid "bump" (top-of-category for a purchased window).
 * Refresh only returns a listing to its natural place in the recency order;
 * bump will sort above that order entirely. See docs/ROADMAP.md.
 */
class ListingRefreshService
{
    /**
     * Cheap by construction: a status check plus date arithmetic, no queries.
     * That is what lets ProductResource call this per product across a whole
     * page without a cost.
     */
    public function eligibilityFor(Product $product): RefreshEligibility
    {
        if ($product->status !== 'active') {
            return RefreshEligibility::blocked('not_active');
        }

        // The one rule, expressed against the feed's own recency axis so the
        // eligibility check and the ordering can't drift apart. Covers "too new
        // to renew" and "renewed too recently" in a single test, and measures a
        // review-gated listing from approval rather than from when the seller
        // started drafting it.
        $eligibleAt = $product->feed_recency_at
            ->copy()
            ->addDays((int) config('tavan.refresh_min_age_days'));

        return $eligibleAt->isFuture()
            ? RefreshEligibility::blocked('too_recent', $eligibleAt)
            : RefreshEligibility::allowed();
    }

    public function refresh(User $actor, Product $product): Product
    {
        $eligibility = $this->eligibilityFor($product);

        if (! $eligibility->canRefresh) {
            throw $eligibility->blockedReason === 'too_recent'
                ? new RefreshTooRecentException($eligibility->nextEligibleAt)
                : new RefreshNotEligibleException('Samo aktivne oglase možeš osvježiti.');
        }

        $now = now();

        DB::transaction(function () use ($actor, $product, $now) {
            // Base query builder, NOT $product->update(): Eloquent would inject
            // updated_at and fire model events, which would re-trigger the
            // observer, the activity log and the wishlist jobs for what is not
            // a content change. Same reasoning as
            // NotifyFollowersOfNewListingsCommand.
            DB::table('products')
                ->where('id', $product->id)
                ->update(['refreshed_at' => $now]);

            // Log only — nothing reads this for enforcement (refresh is
            // deliberately unmetered). It exists for usage analytics and so
            // abuse would be visible if it ever appeared.
            ListingRefresh::create([
                'user_id'    => $actor->id,
                'product_id' => $product->id,
            ]);
        });

        // Reflect the write on the in-memory model without a re-query, and mark
        // it clean so a later save() doesn't try to write it back.
        $product->refreshed_at = $now;
        $product->syncOriginalAttribute('refreshed_at');

        return $product;
    }
}
