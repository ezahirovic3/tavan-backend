<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Nullable — stays null until App\Console\Commands\NotifyFollowersOfNewListingsCommand
            // has fanned out a "new listing" notification to the seller's followers for this
            // product. Keyed off this rather than a generic "status became active" observer hook
            // so reactivations (order/trade cancellation, deletion-cancel) never re-notify.
            $table->timestamp('followers_notified_at')->nullable()->after('status');
            $table->index(['status', 'followers_notified_at']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['status', 'followers_notified_at']);
            $table->dropColumn('followers_notified_at');
        });
    }
};
