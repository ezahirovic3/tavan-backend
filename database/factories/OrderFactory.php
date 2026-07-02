<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_number'    => '#' . fake()->unique()->numerify('#####'),
            'buyer_id'        => User::factory(),
            'seller_id'       => User::factory(),
            'offer_id'        => null,
            'trade_id'        => null,
            'subtotal'        => 50.00,
            'discount'        => 0.00,
            'shipping_cost'   => 5.00,
            'total'           => 55.00,
            'payment_method'  => 'cash',
            'delivery_method' => 'delivery',
            'status'          => 'pending',
            'shipping_name'   => fake()->name(),
            'shipping_street' => fake()->streetAddress(),
            'shipping_city'   => 'Sarajevo',
            'shipping_phone'  => '+38761000000',
        ];
    }

    /** Attach a line item for the given product (or a fresh one). */
    public function forProduct(?Product $product = null, float $price = 50.00): static
    {
        return $this->has(
            OrderItem::factory()->state(fn () => [
                'product_id' => $product?->id ?? Product::factory(),
                'price'      => $price,
            ]),
            'items',
        );
    }

    public function accepted(): static
    {
        return $this->state(['status' => 'accepted']);
    }

    public function shipped(): static
    {
        return $this->state(['status' => 'shipped']);
    }

    public function delivered(): static
    {
        return $this->state(['status' => 'delivered']);
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed']);
    }

    public function pickup(): static
    {
        return $this->state([
            'delivery_method' => 'pickup',
            'shipping_cost'   => 0.00,
            'total'           => 50.00,
        ]);
    }
}
