<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_options', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->enum('size', ['S', 'M', 'L']);
            $table->string('label', 128);
            $table->decimal('price', 8, 2);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_options');
    }
};
