<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('seller_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('offered_price', 10, 2);
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined', 'countered'])->default('pending');
            $table->decimal('counter_price', 10, 2)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
