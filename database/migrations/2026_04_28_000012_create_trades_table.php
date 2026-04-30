<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('offered_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUlid('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('seller_id')->constrained('users')->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->enum('status', ['active', 'accepted', 'declined'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
