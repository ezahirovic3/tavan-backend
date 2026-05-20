<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUlid('acquired_via_campaign_id')
                ->nullable()
                ->constrained('campaigns')
                ->nullOnDelete()
                ->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['acquired_via_campaign_id']);
            $table->dropColumn('acquired_via_campaign_id');
        });
    }
};
