<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adds the 'active_users' segment (≥1 completed order or ≥1 published
     * listing) — used for the review-growth outbound push, see
     * docs/review-growth-plan.md item 4 in tavan-mobile.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE announcements MODIFY COLUMN target_group ENUM('all','verified','city','listings_require_review','active_users') NOT NULL DEFAULT 'all'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE announcements MODIFY COLUMN target_group ENUM('all','verified','city','listings_require_review') NOT NULL DEFAULT 'all'");
    }
};
