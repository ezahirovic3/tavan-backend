<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->enum('type', ['link_click', 'app_install']);
            $table->enum('platform', ['ios', 'android', 'desktop'])->nullable();
            $table->enum('outcome', ['app_opened', 'store_redirect'])->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['campaign_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_events');
    }
};
