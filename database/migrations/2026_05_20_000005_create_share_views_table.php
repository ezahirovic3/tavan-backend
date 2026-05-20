<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share_views', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->enum('entity_type', ['product', 'profile']);
            $table->ulid('entity_id');
            $table->enum('platform', ['ios', 'android', 'desktop']);
            $table->enum('outcome', ['app_opened', 'store_redirect', 'unknown']);
            $table->string('referrer')->nullable();
            $table->enum('referrer_platform', ['instagram', 'whatsapp', 'facebook', 'twitter', 'direct', 'other'])->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share_views');
    }
};
