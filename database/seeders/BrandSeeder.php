<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        Brand::firstOrCreate(
            ['slug' => 'ostali'],
            [
                'name'       => 'Ostali',
                'slug'       => 'ostali',
                'is_active'  => true,
                'is_other'   => true,
                'sort_order' => 9999,
            ]
        );
    }
}
