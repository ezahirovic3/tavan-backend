<?php

namespace Tests\Feature\Transactions;

use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferTest extends TestCase
{
    use RefreshDatabase;

    // ─── Making an offer ─────────────────────────────────────────────────────

    public function test_buyer_can_make_an_offer_on_an_active_product(): void
    {
        $buyer   = User::factory()->create();
        $seller  = User::factory()->create();
        $product = Product::factory()->create([
            'seller_id'     => $seller->id,
            'status'        => 'active',
            'allows_offers' => true,
        ]);

        $response = $this->actingAs($buyer)->postJson('/api/v1/offers', [
            'product_id'    => $product->id,
            'offered_price' => 20.00,
            'message'       => 'Može li za 20 KM?',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'offered_price', 'status']])
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('offers', [
            'product_id' => $product->id,
            'buyer_id'   => $buyer->id,
            'status'     => 'pending',
        ]);
    }

    public function test_cannot_make_offer_on_product_that_disallows_offers(): void
    {
        $buyer   = User::factory()->create();
        $product = Product::factory()->noOffers()->create(['status' => 'active']);

        $this->actingAs($buyer)->postJson('/api/v1/offers', [
            'product_id'    => $product->id,
            'offered_price' => 20.00,
        ])->assertStatus(422);
    }

    public function test_cannot_make_offer_on_own_product(): void
    {
        $seller  = User::factory()->create();
        $product = Product::factory()->create([
            'seller_id'     => $seller->id,
            'status'        => 'active',
            'allows_offers' => true,
        ]);

        $this->actingAs($seller)->postJson('/api/v1/offers', [
            'product_id'    => $product->id,
            'offered_price' => 20.00,
        ])->assertStatus(422);
    }

    public function test_cannot_make_offer_on_inactive_product(): void
    {
        $buyer   = User::factory()->create();
        $product = Product::factory()->sold()->create(['allows_offers' => true]);

        $this->actingAs($buyer)->postJson('/api/v1/offers', [
            'product_id'    => $product->id,
            'offered_price' => 20.00,
        ])->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_make_an_offer(): void
    {
        $product = Product::factory()->create(['status' => 'active', 'allows_offers' => true]);

        $this->postJson('/api/v1/offers', [
            'product_id'    => $product->id,
            'offered_price' => 20.00,
        ])->assertStatus(401);
    }

    // ─── Seller responds ─────────────────────────────────────────────────────

    public function test_seller_can_accept_a_pending_offer(): void
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();
        $offer  = Offer::factory()->create([
            'seller_id' => $seller->id,
            'buyer_id'  => $buyer->id,
            'status'    => 'pending',
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/offers/{$offer->id}/accept");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseHas('offers', ['id' => $offer->id, 'status' => 'accepted']);
    }

    public function test_seller_can_decline_a_pending_offer(): void
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();
        $offer  = Offer::factory()->create([
            'seller_id' => $seller->id,
            'buyer_id'  => $buyer->id,
            'status'    => 'pending',
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/offers/{$offer->id}/decline");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'declined');
    }

    public function test_seller_can_counter_a_pending_offer(): void
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();
        $offer  = Offer::factory()->create([
            'seller_id'     => $seller->id,
            'buyer_id'      => $buyer->id,
            'offered_price' => 20.00,
            'status'        => 'pending',
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/offers/{$offer->id}/counter", [
            'counter_price' => 30.00,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'countered');

        $this->assertDatabaseHas('offers', [
            'id'            => $offer->id,
            'status'        => 'countered',
            'counter_price' => 30.00,
        ]);
    }

    public function test_buyer_cannot_respond_to_their_own_offer(): void
    {
        $buyer  = User::factory()->create();
        $seller = User::factory()->create();
        $offer  = Offer::factory()->create([
            'buyer_id'  => $buyer->id,
            'seller_id' => $seller->id,
            'status'    => 'pending',
        ]);

        // Buyer tries to accept — should be forbidden (only seller can respond)
        $this->actingAs($buyer)
            ->postJson("/api/v1/offers/{$offer->id}/accept")
            ->assertStatus(403);
    }

    public function test_seller_cannot_respond_to_a_non_pending_offer(): void
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();
        $offer  = Offer::factory()->accepted()->create([
            'seller_id' => $seller->id,
            'buyer_id'  => $buyer->id,
        ]);

        // Offer is already accepted — respond policy checks isPending()
        $this->actingAs($seller)
            ->postJson("/api/v1/offers/{$offer->id}/decline")
            ->assertStatus(403);
    }

    public function test_third_party_cannot_view_an_offer(): void
    {
        $offer    = Offer::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson("/api/v1/offers/{$offer->id}")
            ->assertStatus(403);
    }
}
