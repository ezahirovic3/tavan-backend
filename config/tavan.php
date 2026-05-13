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

];
