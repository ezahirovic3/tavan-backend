<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Per-category push opt-out, all default true. `notifications_enabled`
            // stays the master switch — these only matter when it's on. Muting a
            // category only suppresses the push; the in-app notifications list
            // (App\Notifications\UserActivityNotification) still records every
            // event regardless of these flags.
            $table->boolean('notify_messages')->default(true)->after('notifications_enabled');
            $table->boolean('notify_orders')->default(true)->after('notify_messages');
            $table->boolean('notify_activity')->default(true)->after('notify_orders');
            $table->boolean('notify_price_drops')->default(true)->after('notify_activity');
            $table->boolean('notify_announcements')->default(true)->after('notify_price_drops');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'notify_messages',
                'notify_orders',
                'notify_activity',
                'notify_price_drops',
                'notify_announcements',
            ]);
        });
    }
};
