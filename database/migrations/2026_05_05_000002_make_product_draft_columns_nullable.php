<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('title')->nullable()->change();
            $table->decimal('price', 10, 2)->nullable()->change();
            $table->enum('shipping_size', ['S', 'M', 'L'])->nullable()->change();
            $table->string('location', 128)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('title')->nullable(false)->change();
            $table->decimal('price', 10, 2)->nullable(false)->change();
            $table->enum('shipping_size', ['S', 'M', 'L'])->nullable(false)->change();
            $table->string('location', 128)->nullable(false)->change();
        });
    }
};
