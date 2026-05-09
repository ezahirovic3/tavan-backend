<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing null values to 'user' first, then tighten the column to NOT NULL.
        DB::table('users')->whereNull('role')->update(['role' => 'user']);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('user','admin','super_admin') NOT NULL DEFAULT 'user'");
    }

    public function down(): void
    {
        // Revert: set 'user' rows back to null, restore old nullable enum
        DB::table('users')->where('role', 'user')->update(['role' => null]);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','super_admin') NULL DEFAULT NULL");
    }
};
