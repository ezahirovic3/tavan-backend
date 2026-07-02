<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });

        // Backfill: one item per existing order, priced at the order subtotal
        // (= product price at order time; offer discounts live on the order).
        DB::table('orders')
            ->whereNotNull('product_id')
            ->orderBy('id')
            ->each(function ($order) {
                DB::table('order_items')->insert([
                    'id'         => (string) Str::ulid(),
                    'order_id'   => $order->id,
                    'product_id' => $order->product_id,
                    'price'      => $order->subtotal,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->created_at,
                ]);
            });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignUlid('product_id')->nullable()->after('seller_id')
                ->constrained()->nullOnDelete();
        });

        DB::table('order_items')
            ->orderBy('order_id')
            ->orderBy('id')
            ->each(function ($item) {
                DB::table('orders')
                    ->where('id', $item->order_id)
                    ->whereNull('product_id')
                    ->update(['product_id' => $item->product_id]);
            });

        Schema::dropIfExists('order_items');
    }
};
