<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE products MODIFY COLUMN status ENUM('draft','active','reserved','sold') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        // Move any reserved products back to active before removing the value
        DB::table('products')->where('status', 'reserved')->update(['status' => 'active']);
        DB::statement("ALTER TABLE products MODIFY COLUMN status ENUM('draft','active','sold') NOT NULL DEFAULT 'draft'");
    }
};
