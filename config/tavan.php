<?php

return [

    /*
     * The fixed ULID for the "Tavan Podrška" system user.
     * This user acts as the participant in all admin→user support conversations.
     * Admin replies are stored with sender_id = this ID; the real admin is in payload.admin_id.
     */
    'system_user_id' => '01TAVANSYSTEMSUPPORT000000',

    /*
     * When false, newly registered users can publish listings immediately (no admin review).
     * Flip to true once the marketplace is established to require admin approval for new sellers.
     */
    'listings_require_review' => env('LISTINGS_REQUIRE_REVIEW', false),

    /*
     * Marketing milestone: when the number of active listings first reaches this
     * value, the listing that crossed the line is flagged in the activity log
     * (log_name "milestone") and admins get a Filament database notification.
     */
    'active_product_milestone' => env('ACTIVE_PRODUCT_MILESTONE', 1000),

    /*
     * Wishlist price-drop notifications (App\Jobs\NotifyWishlistPriceDrop,
     * triggered from App\Observers\ProductObserver::checkPriceDrop).
     * A drop only fans out to wishlisters if it clears EITHER threshold, and
     * only once per product per cooldown window — otherwise a seller nudging
     * price up/down repeatedly would spam every wishlister on each edit.
     */
    'price_drop_min_percent' => (float) env('PRICE_DROP_MIN_PERCENT', 5),
    'price_drop_min_amount' => (float) env('PRICE_DROP_MIN_AMOUNT', 5),
    'price_drop_cooldown_hours' => (int) env('PRICE_DROP_COOLDOWN_HOURS', 48),

    /*
     * How long to wait after a followed seller's most recent new listing before
     * fanning out a "new listing(s)" notification to their followers — batches
     * a multi-listing session into one notification instead of one per listing.
     * See App\Console\Commands\NotifyFollowersOfNewListingsCommand.
     */
    'follow_notification_quiet_minutes' => (int) env('FOLLOW_NOTIFICATION_QUIET_MINUTES', 30),

];
