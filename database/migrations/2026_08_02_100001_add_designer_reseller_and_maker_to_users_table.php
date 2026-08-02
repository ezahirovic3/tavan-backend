<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_designer_reseller')->default(false)->after('is_vintage_seller');
            $table->boolean('is_designer_maker')->default(false)->after('is_designer_reseller');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_designer_reseller', 'is_designer_maker']);
        });
    }
};
