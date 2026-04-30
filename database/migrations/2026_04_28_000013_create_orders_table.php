<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('order_number', 32)->unique();
            $table->foreignUlid('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('offer_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('payment_method', 64);
            $table->string('delivery_method', 64);
            $table->enum('status', [
                'pending',
                'accepted',
                'shipped',
                'delivered',
                'completed',
                'declined',
            ])->default('pending');
            $table->string('shipping_name')->nullable();
            $table->string('shipping_street')->nullable();
            $table->string('shipping_city', 128)->nullable();
            $table->string('shipping_phone', 32)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
