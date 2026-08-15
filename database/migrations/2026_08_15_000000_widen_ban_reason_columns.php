<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * users.ban_reason and banned_devices.reason were created as VARCHAR(255).
     * The admin panel collects the reason via an unbounded Textarea, and the
     * DB connection runs in strict mode, so a reason over 255 chars throws
     * SQLSTATE[22001] "Data too long for column" instead of saving — this is
     * what surfaces in Filament as the generic "Error while loading page"
     * toast when banning a user. Widen both columns to TEXT.
     *
     * Uses raw ALTER statements (matching the pattern already used by the
     * products.status enum migrations) since doctrine/dbal isn't installed.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE users MODIFY COLUMN ban_reason TEXT NULL');
        DB::statement('ALTER TABLE banned_devices MODIFY COLUMN reason TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users MODIFY COLUMN ban_reason VARCHAR(255) NULL');
        DB::statement('ALTER TABLE banned_devices MODIFY COLUMN reason VARCHAR(255) NULL');
    }
};
