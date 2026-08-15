<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * users.banned_until was created as TIMESTAMP, which MySQL caps at
     * 2038-01-19 03:14:07 (the 32-bit Unix-time ceiling) — it can never
     * store a later date, no matter the PHP/Carbon value passed in.
     * BanService::ban() represents a permanent ban as Carbon::create(2099, 1, 1),
     * so every "Permanentno" ban throws SQLSTATE[22007] "Invalid datetime value"
     * on save. DATETIME has no such ceiling (up to year 9999), so switch to that.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE users MODIFY COLUMN banned_until DATETIME NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users MODIFY COLUMN banned_until TIMESTAMP NULL');
    }
};
