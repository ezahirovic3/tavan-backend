<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id'    => Product::factory(),
            'buyer_id'      => User::factory(),
            'seller_id'     => User::factory(),
            'offered_price' => fake()->randomFloat(2, 5, 100),
            'message'       => null,
            'status'        => 'pending',
            'counter_price' => null,
            'expires_at'    => now()->addDays(3),
        ];
    }

    public function accepted(): static
    {
        return $this->state(['status' => 'accepted']);
    }

    public function declined(): static
    {
        return $this->state(['status' => 'declined']);
    }
}
