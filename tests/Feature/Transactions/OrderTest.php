<?php

namespace Tests\Feature\Transactions;

use App\Models\Offer;
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
            ->assertJsonStructure(['data' => ['id', 'orderNumber', 'status', 'total', 'items']])
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.product.id', $product->id)
            ->assertJsonPath('data.items.0.product.id', $product->id);

        $this->assertDatabaseHas('orders', [
            'buyer_id'  => $buyer->id,
            'seller_id' => $seller->id,
            'status'    => 'pending',
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id'   => $response->json('data.id'),
            'product_id' => $product->id,
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

    // ─── Bundle buy ──────────────────────────────────────────────────────────

    public function test_buyer_can_create_a_bundle_order(): void
    {
        $buyer  = User::factory()->create();
        $seller = User::factory()->create();
        $cheap  = Product::factory()->create([
            'seller_id'            => $seller->id,
            'status'               => 'active',
            'price'                => 20,
            'free_shipping'        => false,
            'exact_shipping_price' => 4,
        ]);
        $pricey = Product::factory()->create([
            'seller_id'            => $seller->id,
            'status'               => 'active',
            'price'                => 30,
            'free_shipping'        => false,
            'exact_shipping_price' => 7,
        ]);

        $response = $this->actingAs($buyer)->postJson('/api/v1/orders', [
            'product_ids'     => [$cheap->id, $pricey->id],
            'payment_method'  => 'cash',
            'delivery_method' => 'delivery',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.subtotal', 50)
            // Bundle ships as one parcel — max of the per-item costs
            ->assertJsonPath('data.shippingCost', 7)
            ->assertJsonPath('data.total', 57);

        $this->assertEquals('reserved', $cheap->fresh()->status);
        $this->assertEquals('reserved', $pricey->fresh()->status);
    }

    public function test_bundle_shipping_is_free_only_when_all_items_ship_free(): void
    {
        $buyer  = User::factory()->create();
        $seller = User::factory()->create();
        $products = Product::factory()->count(2)->create([
            'seller_id'     => $seller->id,
            'status'        => 'active',
            'free_shipping' => true,
        ]);

        $response = $this->actingAs($buyer)->postJson('/api/v1/orders', [
            'product_ids'     => $products->pluck('id')->all(),
            'payment_method'  => 'cash',
            'delivery_method' => 'delivery',
        ]);

        $response->assertStatus(201)->assertJsonPath('data.shippingCost', 0);
    }

    public function test_bundle_items_must_all_be_from_the_same_seller(): void
    {
        $buyer    = User::factory()->create();
        $productA = Product::factory()->create(['status' => 'active']);
        $productB = Product::factory()->create(['status' => 'active']); // different seller

        $this->actingAs($buyer)->postJson('/api/v1/orders', [
            'product_ids'     => [$productA->id, $productB->id],
            'payment_method'  => 'cash',
            'delivery_method' => 'pickup',
        ])->assertStatus(422)->assertJsonValidationErrors('productIds');
    }

    public function test_bundle_with_an_unavailable_item_is_rejected_and_names_it(): void
    {
        $buyer  = User::factory()->create();
        $seller = User::factory()->create();
        $active = Product::factory()->create(['seller_id' => $seller->id, 'status' => 'active']);
        $sold   = Product::factory()->create(['seller_id' => $seller->id, 'status' => 'sold', 'title' => 'Prodana jakna']);

        $response = $this->actingAs($buyer)->postJson('/api/v1/orders', [
            'product_ids'     => [$active->id, $sold->id],
            'payment_method'  => 'cash',
            'delivery_method' => 'pickup',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Prodana jakna', $response->json('message'));

        // Nothing was reserved — the whole bundle is rejected atomically
        $this->assertEquals('active', $active->fresh()->status);
    }

    public function test_bundle_containing_own_product_is_rejected(): void
    {
        $buyer  = User::factory()->create();
        $seller = User::factory()->create();
        $theirs = Product::factory()->create(['seller_id' => $seller->id, 'status' => 'active']);
        $mine   = Product::factory()->create(['seller_id' => $buyer->id, 'status' => 'active']);

        $this->actingAs($buyer)->postJson('/api/v1/orders', [
            'product_ids'     => [$theirs->id, $mine->id],
            'payment_method'  => 'cash',
            'delivery_method' => 'pickup',
        ])->assertStatus(422);
    }

    public function test_offer_cannot_be_applied_to_a_bundle(): void
    {
        $buyer    = User::factory()->create();
        $seller   = User::factory()->create();
        $products = Product::factory()->count(2)->create(['seller_id' => $seller->id, 'status' => 'active']);

        $offer = Offer::factory()->create([
            'buyer_id'   => $buyer->id,
            'seller_id'  => $seller->id,
            'product_id' => $products[0]->id,
            'status'     => 'accepted',
        ]);

        $this->actingAs($buyer)->postJson('/api/v1/orders', [
            'product_ids'     => $products->pluck('id')->all(),
            'offer_id'        => $offer->id,
            'payment_method'  => 'cash',
            'delivery_method' => 'pickup',
        ])->assertStatus(422)->assertJsonValidationErrors('offerId');
    }

    public function test_accepted_offer_still_discounts_a_single_item_order(): void
    {
        $buyer   = User::factory()->create();
        $seller  = User::factory()->create();
        $product = Product::factory()->create([
            'seller_id'     => $seller->id,
            'status'        => 'active',
            'price'         => 50,
            'free_shipping' => true,
        ]);

        $offer = Offer::factory()->create([
            'buyer_id'      => $buyer->id,
            'seller_id'     => $seller->id,
            'product_id'    => $product->id,
            'offered_price' => 40,
            'status'        => 'accepted',
        ]);

        $response = $this->actingAs($buyer)->postJson('/api/v1/orders', [
            'product_id'      => $product->id,
            'offer_id'        => $offer->id,
            'payment_method'  => 'cash',
            'delivery_method' => 'delivery',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.discount', 10)
            ->assertJsonPath('data.total', 40);

        $this->assertEquals('ordered', $offer->fresh()->status);
    }

    public function test_someone_elses_offer_cannot_be_applied(): void
    {
        $buyer      = User::factory()->create();
        $otherBuyer = User::factory()->create();
        $seller     = User::factory()->create();
        $product    = Product::factory()->create(['seller_id' => $seller->id, 'status' => 'active', 'price' => 50]);

        $offer = Offer::factory()->create([
            'buyer_id'      => $otherBuyer->id,
            'seller_id'     => $seller->id,
            'product_id'    => $product->id,
            'offered_price' => 1,
            'status'        => 'accepted',
        ]);

        $this->actingAs($buyer)->postJson('/api/v1/orders', [
            'product_id'      => $product->id,
            'offer_id'        => $offer->id,
            'payment_method'  => 'cash',
            'delivery_method' => 'pickup',
        ])->assertStatus(422)->assertJsonValidationErrors('offerId');
    }

    // ─── Multi-item orders (bundle groundwork) ───────────────────────────────

    public function test_completing_a_multi_item_order_marks_all_products_sold(): void
    {
        [$buyer, $seller, $order, $products] = $this->makeMultiItemOrder(['status' => 'delivered']);

        $this->actingAs($buyer)
            ->postJson("/api/v1/orders/{$order->id}/complete")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonCount(2, 'data.items');

        foreach ($products as $product) {
            $this->assertEquals('sold', $product->fresh()->status);
        }
    }

    public function test_declining_a_multi_item_order_reactivates_all_products(): void
    {
        [$buyer, $seller, $order, $products] = $this->makeMultiItemOrder(['status' => 'pending']);

        $this->actingAs($seller)
            ->postJson("/api/v1/orders/{$order->id}/decline")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'declined');

        foreach ($products as $product) {
            $this->assertEquals('active', $product->fresh()->status);
        }
    }

    public function test_order_response_exposes_first_item_as_product_for_backward_compat(): void
    {
        [$buyer, $seller, $order, $products] = $this->makeMultiItemOrder();

        $this->actingAs($buyer)
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.product.id', $products[0]->id)
            ->assertJsonCount(2, 'data.items');
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
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonPath('meta.total', 5);

        // Should see only orders where user is buyer or seller (5 total, asserted above)
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
        // meta keys are camelCase via response middleware (e.g. currentPage, lastPage)
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
     * Creates a buyer, a seller, a product (owned by seller), and an order
     * with a single line item for that product.
     *
     * @return array{0: User, 1: User, 2: Order}
     */
    private function makePendingOrder(array $orderOverrides = []): array
    {
        $buyer   = User::factory()->create();
        $seller  = User::factory()->create();
        $product = Product::factory()->create(['seller_id' => $seller->id, 'status' => 'active']);

        $order = Order::factory()->forProduct($product)->create(array_merge([
            'buyer_id'  => $buyer->id,
            'seller_id' => $seller->id,
        ], $orderOverrides));

        return [$buyer, $seller, $order];
    }

    /**
     * Creates an order with two line items from the same seller.
     * Products start 'reserved' (buyer committed, order in flight).
     *
     * @return array{0: User, 1: User, 2: Order, 3: array<Product>}
     */
    private function makeMultiItemOrder(array $orderOverrides = []): array
    {
        $buyer    = User::factory()->create();
        $seller   = User::factory()->create();
        $products = Product::factory()->count(2)->create(['seller_id' => $seller->id, 'status' => 'reserved']);

        $order = Order::factory()
            ->forProduct($products[0])
            ->forProduct($products[1])
            ->create(array_merge([
                'buyer_id'  => $buyer->id,
                'seller_id' => $seller->id,
            ], $orderOverrides));

        return [$buyer, $seller, $order, $products->all()];
    }
}
