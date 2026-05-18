<?php

namespace Tests\Feature\Products;

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    // ─── Public browsing ─────────────────────────────────────────────────────

    public function test_anyone_can_browse_active_products(): void
    {
        Product::factory()->count(3)->create(['status' => 'active']);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'title', 'price']],
                'meta' => ['currentPage', 'lastPage', 'perPage', 'total'],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_draft_and_sold_products_do_not_appear_in_public_listing(): void
    {
        Product::factory()->create(['status' => 'active']);
        Product::factory()->create(['status' => 'draft']);
        Product::factory()->create(['status' => 'sold']);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_anyone_can_view_a_single_product(): void
    {
        $product = Product::factory()->create(['status' => 'active']);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'title', 'price']]);
    }

    // ─── Creating products ───────────────────────────────────────────────────

    public function test_authenticated_user_can_create_a_product(): void
    {
        $seller = User::factory()->create();
        $brand  = Brand::factory()->create();

        $response = $this->actingAs($seller)->postJson('/api/v1/products', [
            'title'         => 'Test jakna',
            'description'   => 'Dobro stanje',
            'price'         => 25.00,
            'root_category' => 'women',
            'category'      => 'tops',
            'condition'     => 'good',
            'shipping_size' => 'M',
            'location'      => 'Sarajevo',
            'brand_id'      => $brand->id,
            'allows_offers' => true,
            'allows_trades' => false,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'title', 'price', 'status']]);

        $this->assertDatabaseHas('products', [
            'seller_id' => $seller->id,
            'title'     => 'Test jakna',
        ]);
    }

    public function test_trusted_seller_product_goes_active_immediately(): void
    {
        $seller = User::factory()->create(['listings_require_review' => false]);
        $brand  = Brand::factory()->create();

        $response = $this->actingAs($seller)->postJson('/api/v1/products', [
            'title'         => 'Test jakna',
            'price'         => 25.00,
            'root_category' => 'women',
            'category'      => 'tops',
            'condition'     => 'good',
            'shipping_size' => 'M',
            'location'      => 'Sarajevo',
            'brand_id'      => $brand->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_untrusted_seller_product_goes_into_pending_review(): void
    {
        $seller = User::factory()->create(['listings_require_review' => true]);
        $brand  = Brand::factory()->create();

        $response = $this->actingAs($seller)->postJson('/api/v1/products', [
            'title'         => 'Test jakna',
            'price'         => 25.00,
            'root_category' => 'women',
            'category'      => 'tops',
            'condition'     => 'good',
            'shipping_size' => 'M',
            'location'      => 'Sarajevo',
            'brand_id'      => $brand->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending_review');
    }

    public function test_product_can_be_saved_as_draft(): void
    {
        $seller = User::factory()->create();

        $response = $this->actingAs($seller)->postJson('/api/v1/products', [
            'status' => 'draft',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_unauthenticated_user_cannot_create_a_product(): void
    {
        $this->postJson('/api/v1/products', ['title' => 'Test'])->assertStatus(401);
    }

    // ─── Updating products ───────────────────────────────────────────────────

    public function test_owner_can_update_their_product(): void
    {
        $seller  = User::factory()->create();
        $product = Product::factory()->create(['seller_id' => $seller->id]);

        $response = $this->actingAs($seller)->patchJson("/api/v1/products/{$product->id}", [
            'title' => 'Novo ime',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Novo ime');
    }

    public function test_user_cannot_update_someone_elses_product(): void
    {
        $product  = Product::factory()->create();
        $attacker = User::factory()->create();

        $this->actingAs($attacker)
            ->patchJson("/api/v1/products/{$product->id}", ['title' => 'Hacked'])
            ->assertStatus(403);
    }

    // ─── Deleting products ───────────────────────────────────────────────────

    public function test_owner_can_delete_their_product(): void
    {
        $seller  = User::factory()->create();
        $product = Product::factory()->create(['seller_id' => $seller->id]);

        $this->actingAs($seller)
            ->deleteJson("/api/v1/products/{$product->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_user_cannot_delete_someone_elses_product(): void
    {
        $product  = Product::factory()->create();
        $attacker = User::factory()->create();

        $this->actingAs($attacker)
            ->deleteJson("/api/v1/products/{$product->id}")
            ->assertStatus(403);
    }

    // ─── Publishing ──────────────────────────────────────────────────────────

    public function test_owner_can_publish_a_draft_product(): void
    {
        $seller  = User::factory()->create(['listings_require_review' => false]);
        $product = Product::factory()->draft()->create(['seller_id' => $seller->id]);

        $response = $this->actingAs($seller)
            ->postJson("/api/v1/products/{$product->id}/publish");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_user_cannot_publish_someone_elses_product(): void
    {
        $product  = Product::factory()->draft()->create();
        $attacker = User::factory()->create();

        $this->actingAs($attacker)
            ->postJson("/api/v1/products/{$product->id}/publish")
            ->assertStatus(403);
    }

    public function test_cannot_publish_an_already_active_product(): void
    {
        $seller  = User::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'active',
        ]);

        $this->actingAs($seller)
            ->postJson("/api/v1/products/{$product->id}/publish")
            ->assertStatus(422);
    }

    // ─── Filtering ───────────────────────────────────────────────────────────

    public function test_products_can_be_filtered_by_category(): void
    {
        Product::factory()->create(['status' => 'active', 'root_category' => 'women']);
        Product::factory()->create(['status' => 'active', 'root_category' => 'men']);

        $response = $this->getJson('/api/v1/products?root_category=women');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('women', $response->json('data.0.rootCategory'));
    }

    public function test_products_can_be_searched_by_title(): void
    {
        Product::factory()->create(['status' => 'active', 'title' => 'Crvena haljina']);
        Product::factory()->create(['status' => 'active', 'title' => 'Plava majica']);

        $response = $this->getJson('/api/v1/products?q=haljina');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertStringContainsString('haljina', strtolower($response->json('data.0.title')));
    }
}
