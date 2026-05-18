<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Orders: make product_id nullable + switch cascade→null + add soft deletes
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->ulid('product_id')->nullable()->change();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->softDeletes();
        });

        // Offers: make product_id nullable + switch cascade→null + add soft deletes
        Schema::table('offers', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->ulid('product_id')->nullable()->change();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['product_id']);
            $table->ulid('product_id')->nullable(false)->change();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['product_id']);
            $table->ulid('product_id')->nullable(false)->change();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }
};
