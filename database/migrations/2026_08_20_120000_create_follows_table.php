<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('follower_id');
            $table->ulid('followed_id');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('follower_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('followed_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['follower_id', 'followed_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
