<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name'      => $name,
            'slug'      => Str::slug($name),
            'logo_url'  => null,
            'is_active' => true,
            'is_other'  => false,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
