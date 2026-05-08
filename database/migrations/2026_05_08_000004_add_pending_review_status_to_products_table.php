<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add pending_review to the status enum
        DB::statement("ALTER TABLE products MODIFY COLUMN status ENUM('draft','pending_review','active','reserved','sold') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        // Move any pending_review products back to draft before removing the value
        DB::table('products')->where('status', 'pending_review')->update(['status' => 'draft']);
        DB::statement("ALTER TABLE products MODIFY COLUMN status ENUM('draft','active','reserved','sold') NOT NULL DEFAULT 'draft'");
    }
};
