<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // true  = new listings go to pending_review (new users + restricted users)
            // false = new listings go straight to active (trusted sellers)
            $table->boolean('listings_require_review')->default(true)->after('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('listings_require_review');
        });
    }
};
