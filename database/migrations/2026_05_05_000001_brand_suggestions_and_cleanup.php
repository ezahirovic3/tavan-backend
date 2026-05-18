<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('brands', 'is_other')) {
            Schema::table('brands', function (Blueprint $table) {
                $table->boolean('is_other')->default(false)->after('is_active');
            });
        }

        if (Schema::hasColumn('products', 'brand_custom')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('brand_custom');
            });
        }

        if (! Schema::hasTable('brand_suggestions')) {
        Schema::create('brand_suggestions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_suggestions');

        Schema::table('products', function (Blueprint $table) {
            $table->string('brand_custom')->nullable()->after('brand_id');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('is_other');
        });
    }
};
