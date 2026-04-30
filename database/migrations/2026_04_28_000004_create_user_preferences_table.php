<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('top_sizes')->nullable();
            $table->json('bottom_sizes')->nullable();
            $table->json('shoe_sizes')->nullable();
            $table->json('categories')->nullable();
            $table->json('subcategories')->nullable();
            $table->json('cities')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
