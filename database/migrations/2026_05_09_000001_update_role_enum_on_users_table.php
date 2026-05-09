<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: expand the enum to include 'user' while keeping it nullable
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('user','admin','super_admin') NULL DEFAULT NULL");
        // Step 2: fill existing nulls now that 'user' is a valid enum value
        DB::statement("UPDATE users SET role = 'user' WHERE role IS NULL");
        // Step 3: tighten to NOT NULL with a default
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('user','admin','super_admin') NOT NULL DEFAULT 'user'");
    }

    public function down(): void
    {
        // Revert: set 'user' rows back to null, restore old nullable enum
        DB::table('users')->where('role', 'user')->update(['role' => null]);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','super_admin') NULL DEFAULT NULL");
    }
};
