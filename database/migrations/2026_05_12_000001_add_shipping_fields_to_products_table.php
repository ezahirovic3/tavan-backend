<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('pickup_enabled')->nullable()->after('shipping_size');
            $table->boolean('free_shipping')->nullable()->after('pickup_enabled');
            $table->decimal('exact_shipping_price', 10, 2)->nullable()->after('free_shipping');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['pickup_enabled', 'free_shipping', 'exact_shipping_price']);
        });
    }
};
