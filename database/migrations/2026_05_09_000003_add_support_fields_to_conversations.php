<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->enum('type', ['user', 'admin_support'])->default('user')->after('product_id');
            $table->boolean('allow_replies')->nullable()->after('type');
            $table->enum('status', ['open', 'resolved'])->nullable()->after('allow_replies');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['type', 'allow_replies', 'status']);
        });
    }
};
