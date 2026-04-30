<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('sender_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', [
                'text',
                'system_inquiry',
                'system_offer',
                'system_order',
                'system_trade',
                'system_status',
            ]);
            $table->text('body')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
