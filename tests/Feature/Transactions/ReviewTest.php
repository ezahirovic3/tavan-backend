<?php

namespace Tests\Feature\Transactions;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_review_seller_after_a_completed_order(): void
    {
        [$buyer, $seller, $order] = $this->makeCompletedOrder();

        $response = $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/reviews", [
            'rating'  => 5,
            'comment' => 'Odlična prodaja!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'rating', 'comment']]);

        $this->assertDatabaseHas('reviews', [
            'order_id'    => $order->id,
            'reviewer_id' => $buyer->id,
            'reviewed_id' => $seller->id,
            'rating'      => 5,
        ]);
    }

    public function test_seller_can_review_buyer_after_a_completed_order(): void
    {
        [$buyer, $seller, $order] = $this->makeCompletedOrder();

        $response = $this->actingAs($seller)->postJson("/api/v1/orders/{$order->id}/reviews", [
            'rating'  => 4,
            'comment' => 'Uredan kupac.',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('reviews', [
            'order_id'    => $order->id,
            'reviewer_id' => $seller->id,
            'reviewed_id' => $buyer->id,
            'rating'      => 4,
        ]);
    }

    public function test_cannot_review_an_order_that_is_not_completed(): void
    {
        [$buyer, $seller, $order] = $this->makeCompletedOrder(['status' => 'accepted']);

        $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/reviews", [
            'rating' => 5,
        ])->assertStatus(422);
    }

    public function test_cannot_leave_a_review_twice_for_the_same_order(): void
    {
        [$buyer, $seller, $order] = $this->makeCompletedOrder();

        // First review succeeds
        $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/reviews", [
            'rating' => 5,
        ])->assertStatus(201);

        // Second review by the same user on the same order must fail
        $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/reviews", [
            'rating' => 3,
        ])->assertStatus(422);
    }

    public function test_third_party_cannot_leave_a_review(): void
    {
        [, , $order] = $this->makeCompletedOrder();
        $outsider    = User::factory()->create();

        $this->actingAs($outsider)->postJson("/api/v1/orders/{$order->id}/reviews", [
            'rating' => 5,
        ])->assertStatus(403);
    }

    public function test_review_rating_must_be_between_1_and_5(): void
    {
        [$buyer, , $order] = $this->makeCompletedOrder();

        $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/reviews", [
            'rating' => 6,
        ])->assertStatus(422)->assertJsonValidationErrors('rating');

        $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/reviews", [
            'rating' => 0,
        ])->assertStatus(422)->assertJsonValidationErrors('rating');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * @return array{0: User, 1: User, 2: Order}
     */
    private function makeCompletedOrder(array $overrides = []): array
    {
        $buyer   = User::factory()->create();
        $seller  = User::factory()->create();
        $product = Product::factory()->create(['seller_id' => $seller->id]);

        $order = Order::factory()->completed()->create(array_merge([
            'buyer_id'   => $buyer->id,
            'seller_id'  => $seller->id,
            'product_id' => $product->id,
        ], $overrides));

        return [$buyer, $seller, $order];
    }
}
