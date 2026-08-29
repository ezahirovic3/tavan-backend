<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_refreshes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            // Nothing enforces a quota today — refresh is deliberately unmetered.
            // This log exists for usage analytics (input for pricing the future paid
            // bump) and so scripted abuse would be visible if it ever appeared.
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_refreshes');
    }
};
