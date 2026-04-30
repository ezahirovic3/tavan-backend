<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('brand_custom')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('root_category', ['women', 'men'])->nullable();
            $table->string('category', 128)->nullable();
            $table->string('subcategory', 128)->nullable();
            $table->enum('condition', ['novo', 'kao_novo', 'odlican', 'dobar', 'zadrzavajuci'])->nullable();
            $table->string('size', 32)->nullable();
            $table->string('color', 64)->nullable();
            $table->string('material', 128)->nullable();
            $table->decimal('price', 10, 2);
            $table->boolean('allows_trades')->default(false);
            $table->boolean('allows_offers')->default(false);
            $table->enum('shipping_size', ['S', 'M', 'L']);
            $table->string('location', 128);
            $table->enum('status', ['draft', 'active', 'sold'])->default('draft');
            $table->integer('likes')->default(0);
            $table->json('measurements')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
