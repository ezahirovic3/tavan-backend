<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE trades MODIFY COLUMN status ENUM('active', 'accepted', 'declined', 'countered', 'completed') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE trades MODIFY COLUMN status ENUM('active', 'accepted', 'declined') NOT NULL DEFAULT 'active'");
    }
};
