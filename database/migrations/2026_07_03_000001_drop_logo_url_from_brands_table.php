<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Business decision: brand logos are dropped entirely (the R2 brands/logos
// folder is cleaned up manually after deploy).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('logo_url');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('logo_url', 2048)->nullable();
        });
    }
};
