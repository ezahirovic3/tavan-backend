<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * user_reports.status was created with only ['pending','reviewed','dismissed'],
     * but UserReportResource's table filter, status badge colors, and the "ban"
     * row action all treat 'warned', 'restricted', and 'banned' as valid statuses
     * too. Writing 'banned' (the only one actually assigned in code, via the ban
     * action) against the old enum throws SQLSTATE[01000] "Data truncated for
     * column 'status'" under strict mode — surfaced in Filament as a generic
     * "Error while loading page" error when an admin bans a reported user.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE user_reports MODIFY COLUMN status ENUM('pending', 'reviewed', 'dismissed', 'warned', 'restricted', 'banned') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE user_reports MODIFY COLUMN status ENUM('pending', 'reviewed', 'dismissed') NOT NULL DEFAULT 'pending'");
    }
};
