<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('reviewed_id')->constrained('users')->cascadeOnDelete();
            $table->tinyInteger('rating');
            $table->text('comment')->nullable();
            $table->enum('role', ['buyer', 'seller']);
            $table->timestamp('created_at')->useCurrent();

            // One review per person per order
            $table->unique(['order_id', 'reviewer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
