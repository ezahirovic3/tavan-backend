<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('vintage_status', 20)->nullable()->after('measurements');  // pending | approved | rejected
            $table->string('vintage_era', 10)->nullable()->after('vintage_status');  // 50s | 60s | 70s | 80s | 90s | y2k
            $table->text('vintage_notes')->nullable()->after('vintage_era');
            $table->string('vintage_provenance', 500)->nullable()->after('vintage_notes');
            $table->string('vintage_reject_reason', 500)->nullable()->after('vintage_provenance');
            $table->string('vintage_reviewed_by', 26)->nullable()->after('vintage_reject_reason');
            $table->timestamp('vintage_reviewed_at')->nullable()->after('vintage_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'vintage_status',
                'vintage_era',
                'vintage_notes',
                'vintage_provenance',
                'vintage_reject_reason',
                'vintage_reviewed_by',
                'vintage_reviewed_at',
            ]);
        });
    }
};
