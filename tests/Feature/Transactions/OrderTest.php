<?php

namespace Tests\Feature\Transactions;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    // ─── Creating an order ───────────────────────────────────────────────────

    public function test_buyer_can_create_a_direct_order(): void
    {
        $buyer   = User::factory()->create();
        $seller  = User::factory()->create();
        $product = Product::factory()->create([
            'seller_id'    => $seller->id,
            'status'       => 'active',
            'free_shipping' => true,
        ]);

        $response = $this->actingAs($buyer)->postJson('/api/v1/orders', [
            'product_id'      => $product->id,
            'payment_method'  => 'cash',
            'delivery_method' => 'pickup',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'order_number', 'status', 'total']])
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('orders', [
            'buyer_id'   => $buyer->id,
            'seller_id'  => $seller->id,
            'product_id' => $product->id,
            'status'     => 'pending',
        ]);

        // Product should be reserved while waiting for seller confirmation
        $this->assertEquals('reserved', $product->fresh()->status);
    }

    public function test_cannot_order_own_product(): void
    {
        $seller  = User::factory()->create();
        $product = Product::factory()->create(['seller_id' => $seller->id, 'status' => 'active']);

        $this->actingAs($seller)->postJson('/api/v1/orders', [
            'product_id'      => $product->id,
            'payment_method'  => 'cash',
            'delivery_method' => 'pickup',
        ])->assertStatus(422);
    }

    public function test_cannot_order_an_inactive_product(): void
    {
        $buyer   = User::factory()->create();
        $product = Product::factory()->sold()->create();

        $this->actingAs($buyer)->postJson('/api/v1/orders', [
            'product_id'      => $product->id,
            'payment_method'  => 'cash',
            'delivery_method' => 'pickup',
        ])->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_create_an_order(): void
    {
        $product = Product::factory()->create(['status' => 'active']);

        $this->postJson('/api/v1/orders', [
            'product_id'      => $product->id,
            'payment_method'  => 'cash',
            'delivery_method' => 'pickup',
        ])->assertStatus(401);
    }

    // ─── Order lifecycle ─────────────────────────────────────────────────────

    public function test_seller_can_accept_a_pending_order(): void
    {
        [$buyer, $seller, $order] = $this->makePendingOrder();

        $response = $this->actingAs($seller)->postJson("/api/v1/orders/{$order->id}/accept");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'accepted');
    }

    public function test_seller_can_ship_an_accepted_order(): void
    {
        [$buyer, $seller, $order] = $this->makePendingOrder(['status' => 'accepted']);

        $response = $this->actingAs($seller)->postJson("/api/v1/orders/{$order->id}/ship");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'shipped');
    }

    public function test_cannot_ship_a_non_accepted_order(): void
    {
        [$buyer, $seller, $order] = $this->makePendingOrder(['status' => 'pending']);

        $this->actingAs($seller)
            ->postJson("/api/v1/orders/{$order->id}/ship")
            ->assertStatus(422);
    }

    public function test_buyer_can_mark_order_as_delivered(): void
    {
        [$buyer, $seller, $order] = $this->makePendingOrder(['status' => 'shipped']);

        $response = $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/deliver");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'delivered');
    }

    public function test_buyer_can_complete_a_delivered_order(): void
    {
        [$buyer, $seller, $order] = $this->makePendingOrder(['status' => 'delivered']);
        $product = $order->product;

        $response = $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/complete");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');

        $this->assertEquals('sold', $product->fresh()->status);
    }

    public function test_buyer_can_cancel_a_pending_order(): void
    {
        [$buyer, $seller, $order] = $this->makePendingOrder();

        $response = $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/decline");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'declined');
    }

    public function test_seller_can_decline_a_pending_order(): void
    {
        [$buyer, $seller, $order] = $this->makePendingOrder();
        $product = $order->product;

        $response = $this->actingAs($seller)->postJson("/api/v1/orders/{$order->id}/decline");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'declined');

        // Product goes back to active when order is declined
        $this->assertEquals('active', $product->fresh()->status);
    }

    public function test_buyer_cannot_cancel_an_already_accepted_order(): void
    {
        [$buyer, $seller, $order] = $this->makePendingOrder(['status' => 'accepted']);

        $this->actingAs($buyer)
            ->postJson("/api/v1/orders/{$order->id}/decline")
            ->assertStatus(422);
    }

    // ─── Listing orders ──────────────────────────────────────────────────────

    public function test_user_can_list_their_orders(): void
    {
        $user = User::factory()->create();

        Order::factory()->count(2)->create(['buyer_id' => $user->id]);
        Order::factory()->count(3)->create(['seller_id' => $user->id]);
        Order::factory()->count(1)->create(); // unrelated order

        $response = $this->actingAs($user)->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);

        // Should see only orders where user is buyer or seller (5 total)
        $this->assertEquals(5, $response->json('meta.total'));
    }

    public function test_user_can_filter_orders_by_role(): void
    {
        $user = User::factory()->create();
        Order::factory()->count(2)->create(['buyer_id' => $user->id]);
        Order::factory()->count(3)->create(['seller_id' => $user->id]);

        $buyerResponse  = $this->actingAs($user)->getJson('/api/v1/orders?role=buyer');
        $sellerResponse = $this->actingAs($user)->getJson('/api/v1/orders?role=seller');

        $this->assertEquals(2, $buyerResponse->json('meta.total'));
        $this->assertEquals(3, $sellerResponse->json('meta.total'));
    }

    public function test_user_cannot_view_someone_elses_order(): void
    {
        $order    = Order::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertStatus(403);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Creates a buyer, a seller, a product (owned by seller), and an order.
     * The product is kept in 'active' status since orders use the product_id FK.
     *
     * @return array{0: User, 1: User, 2: Order}
     */
    private function makePendingOrder(array $orderOverrides = []): array
    {
        $buyer   = User::factory()->create();
        $seller  = User::factory()->create();
        $product = Product::factory()->create(['seller_id' => $seller->id, 'status' => 'active']);

        $order = Order::factory()->create(array_merge([
            'buyer_id'   => $buyer->id,
            'seller_id'  => $seller->id,
            'product_id' => $product->id,
        ], $orderOverrides));

        return [$buyer, $seller, $order];
    }
}
