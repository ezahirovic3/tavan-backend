<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            // Structured content blocks (replaces the free-form HTML `content` field).
            // Each element: { type, text?, author?, file?, caption?, url? }
            $table->json('blocks')->nullable()->after('content');

            // Keep `content` as nullable fallback for any legacy posts
            $table->longText('content')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn('blocks');
        });
    }
};
