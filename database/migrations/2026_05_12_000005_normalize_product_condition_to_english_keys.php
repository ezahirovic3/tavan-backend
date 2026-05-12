<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Expand enum to accept both old and new values during the transition
        DB::statement("ALTER TABLE products MODIFY COLUMN `condition` ENUM('novo','kao_novo','odlican','dobar','zadrzavajuci','new','very_good','good','worn') NULL");

        DB::statement("UPDATE products SET `condition` = CASE `condition`
            WHEN 'novo'          THEN 'new'
            WHEN 'kao_novo'      THEN 'very_good'
            WHEN 'odlican'       THEN 'good'
            WHEN 'dobar'         THEN 'worn'
            WHEN 'zadrzavajuci'  THEN 'worn'
            ELSE `condition`
        END WHERE `condition` IS NOT NULL");

        // Shrink to only the English keys
        DB::statement("ALTER TABLE products MODIFY COLUMN `condition` ENUM('new','very_good','good','worn') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE products MODIFY COLUMN `condition` ENUM('novo','kao_novo','odlican','dobar','zadrzavajuci','new','very_good','good','worn') NULL");

        DB::statement("UPDATE products SET `condition` = CASE `condition`
            WHEN 'new'       THEN 'novo'
            WHEN 'very_good' THEN 'kao_novo'
            WHEN 'good'      THEN 'odlican'
            WHEN 'worn'      THEN 'dobar'
            ELSE `condition`
        END WHERE `condition` IS NOT NULL");

        DB::statement("ALTER TABLE products MODIFY COLUMN `condition` ENUM('novo','kao_novo','odlican','dobar','zadrzavajuci') NULL");
    }
};
