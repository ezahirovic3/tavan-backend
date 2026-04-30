<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // participant IDs stored sorted (lower < higher) to enforce uniqueness
            $table->foreignUlid('participant_one_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('participant_two_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('product_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['participant_one_id', 'participant_two_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
