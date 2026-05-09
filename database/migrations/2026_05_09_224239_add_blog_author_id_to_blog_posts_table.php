<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->foreignId('blog_author_id')->nullable()->constrained('blog_authors')->nullOnDelete()->after('cover_color');
            $table->dropColumn(['author_name', 'author_avatar']);
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropForeign(['blog_author_id']);
            $table->dropColumn('blog_author_id');
            $table->string('author_name')->default('Tavan tim');
            $table->string('author_avatar', 2048)->nullable();
        });
    }
};
