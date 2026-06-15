<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'seller_id'     => User::factory(),
            'brand_id'      => Brand::factory(),
            'title'         => fake()->sentence(3),
            'description'   => fake()->paragraph(),
            'price'         => fake()->randomFloat(2, 5, 200),
            'root_category' => fake()->randomElement(['women', 'men']),
            'category'      => 'tops',
            'subcategory'   => null,
            'condition'     => 'good',
            'size'          => 'M',
            'color'         => 'black',
            'material'      => null,
            'shipping_size' => 'M',
            'location'      => 'Sarajevo',
            'status'        => 'active',
            'allows_offers' => true,
            'allows_trades' => false,
            'pickup_enabled'       => false,
            'free_shipping'        => true,
            'exact_shipping_price' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function sold(): static
    {
        return $this->state(['status' => 'sold']);
    }

    public function noOffers(): static
    {
        return $this->state(['allows_offers' => false]);
    }
}
