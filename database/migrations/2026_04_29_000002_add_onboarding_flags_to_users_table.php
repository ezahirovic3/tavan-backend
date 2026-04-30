<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('profile_setup_done')->default(false)->after('is_verified');
            $table->boolean('feed_setup_done')->default(false)->after('profile_setup_done');
            $table->boolean('first_listing_coach_seen')->default(false)->after('feed_setup_done');
            $table->boolean('first_draft_coach_seen')->default(false)->after('first_listing_coach_seen');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profile_setup_done',
                'feed_setup_done',
                'first_listing_coach_seen',
                'first_draft_coach_seen',
            ]);
        });
    }
};
