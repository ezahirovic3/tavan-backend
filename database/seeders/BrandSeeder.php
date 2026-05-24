<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'Adidas',
            'ASICS',
            'Balenciaga',
            'Bershka',
            'BOSS',
            'C&A',
            'CALLIOPE',
            'Calvin Klein',
            'Columbia',
            'Cortefiel',
            'Diesel',
            'Giorgio Armani',
            'GUCCI',
            'GUESS',
            'H&M',
            'Koton',
            'Lacoste',
            'LC Waikiki',
            'Lee',
            "Levi's",
            'Lindex',
            'Lotto',
            'Louis Vuitton',
            'Massimo Dutti',
            'Michael Kors',
            'Moschino',
            'New Balance',
            'Nike',
            'Pepe Jeans',
            'Polo Ralph Lauren',
            'Primark',
            'Pull&Bear',
            'PUMA',
            'Reebok',
            'Reserved',
            's.Oliver',
            'Sinsay',
            'Springfield',
            'Stradivarius',
            'Terranova',
            'The North Face',
            'Tommy Hilfiger',
            'U.S. Polo Assn.',
            'Under Armour',
            'Versace',
            'Wrangler',
            'ZARA',
        ];

        foreach ($brands as $index => $name) {
            Brand::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name'       => $name,
                    'is_active'  => true,
                    'is_other'   => false,
                    'sort_order' => $index + 1,
                ]
            );
        }

        Brand::firstOrCreate(
            ['slug' => 'ostali'],
            [
                'name'       => 'Ostali',
                'is_active'  => true,
                'is_other'   => true,
                'sort_order' => 9999,
            ]
        );
    }
}
