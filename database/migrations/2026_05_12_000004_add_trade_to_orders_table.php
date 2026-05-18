<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignUlid('trade_id')->nullable()->constrained('trades')->nullOnDelete()->after('offer_id');
        });

        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('cash','card','bank_transfer','trade') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('cash','card','bank_transfer') NOT NULL");

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['trade_id']);
            $table->dropColumn('trade_id');
        });
    }
};
