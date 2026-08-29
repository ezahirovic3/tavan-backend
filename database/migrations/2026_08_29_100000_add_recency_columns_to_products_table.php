<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // NULL = never refreshed. Written only by App\Services\ListingRefreshService
            // via a base-query UPDATE — no Eloquent events, no updated_at bump (same
            // reasoning as NotifyFollowersOfNewListingsCommand). Deliberately kept out
            // of Product::$fillable so PATCH /products/{id} can never set it.
            $table->timestamp('refreshed_at')->nullable()->after('followers_notified_at');

            // When the listing first became visible to buyers. Stamped once by
            // ProductObserver::saving() on the first transition to 'active' — which is
            // the admin approval moment for review-gated listings, so an approved
            // listing debuts at the top of the feed instead of at its draft age.
            // Never overwritten, so hide->unhide and takedown->restore can't be used as
            // a free refresh.
            $table->timestamp('published_at')->nullable()->after('refreshed_at');
        });

        // Backfill: anything that has been publicly visible gets created_at as the best
        // available approximation. Drafts and pending_review stay NULL — they have never
        // been published, and the observer stamps them when they are.
        // Without this, the observer's null-guard would fire on the next save of every
        // legacy listing and bump it, re-creating the edit-bump behaviour this removes.
        DB::table('products')
            ->whereIn('status', ['active', 'reserved', 'sold'])
            ->whereNull('published_at')
            ->update(['published_at' => DB::raw('created_at')]);

        // Functional index so the default feed
        // (WHERE status = 'active' ORDER BY COALESCE(refreshed_at, published_at, created_at) DESC)
        // is served from the index instead of a filesort. MySQL 8.0.13+.
        // Expression must byte-match Product::scopeApplyFilters().
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                CREATE INDEX products_status_recency_idx
                ON products (status, (COALESCE(refreshed_at, published_at, created_at)))
            ');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('DROP INDEX products_status_recency_idx ON products');
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['refreshed_at', 'published_at']);
        });
    }
};
