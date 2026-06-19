<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('designer_status', 20)->nullable()->after('vintage_reviewed_at');  // pending | approved | rejected
            $table->string('designer_brand', 200)->nullable()->after('designer_status');
            $table->text('designer_notes')->nullable()->after('designer_brand');
            $table->string('designer_reject_reason', 500)->nullable()->after('designer_notes');
            $table->string('designer_reviewed_by', 26)->nullable()->after('designer_reject_reason');
            $table->timestamp('designer_reviewed_at')->nullable()->after('designer_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'designer_status',
                'designer_brand',
                'designer_notes',
                'designer_reject_reason',
                'designer_reviewed_by',
                'designer_reviewed_at',
            ]);
        });
    }
};
