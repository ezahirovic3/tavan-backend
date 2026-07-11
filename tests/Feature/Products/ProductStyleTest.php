<?php

namespace Tests\Feature\Products;

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStyleTest extends TestCase
{
    use RefreshDatabase;

    // ─── Create / update ─────────────────────────────────────────────────────

    public function test_product_can_be_created_with_styles(): void
    {
        $seller = User::factory()->create();
        $brand  = Brand::factory()->create();

        $response = $this->actingAs($seller)->postJson('/api/v1/products', [
            'title'         => 'Y2K top',
            'price'         => 15.00,
            'root_category' => 'women',
            'category'      => 'tops',
            'condition'     => 'good',
            'shipping_size' => 'S',
            'location'      => 'Sarajevo',
            'brand_id'      => $brand->id,
            'styles'        => ['y2k', 'streetwear'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.styles', ['y2k', 'streetwear']);
    }

    public function test_more_than_three_styles_are_rejected(): void
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['seller_id' => $seller->id]);

        $this->actingAs($seller)
            ->patchJson("/api/v1/products/{$product->id}", [
                'styles' => ['y2k', 'streetwear', 'casual', 'boho'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('styles');
    }

    public function test_unknown_style_key_is_rejected(): void
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['seller_id' => $seller->id]);

        $this->actingAs($seller)
            ->patchJson("/api/v1/products/{$product->id}", [
                'styles' => ['vintage'], // vintage is a badge, never a style key
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('styles.0');
    }

    public function test_owner_can_update_styles_on_existing_product(): void
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['seller_id' => $seller->id]);

        $this->actingAs($seller)
            ->patchJson("/api/v1/products/{$product->id}", ['styles' => ['goth_alt']])
            ->assertStatus(200)
            ->assertJsonPath('data.styles', ['goth_alt']);

        $this->assertEquals(['goth_alt'], $product->fresh()->styles);
    }

    public function test_styles_can_be_cleared(): void
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'styles'    => ['boho'],
        ]);

        $this->actingAs($seller)
            ->patchJson("/api/v1/products/{$product->id}", ['styles' => null])
            ->assertStatus(200);

        $this->assertEmpty($product->fresh()->styles);
    }

    // ─── Filtering ────────────────────────────────────────────────────────────

    public function test_products_can_be_filtered_by_styles_any_of(): void
    {
        Product::factory()->create(['title' => 'Goth item', 'styles' => ['goth_alt', 'grunge']]);
        Product::factory()->create(['title' => 'Boho item', 'styles' => ['boho']]);
        Product::factory()->create(['title' => 'Untagged item', 'styles' => null]);

        $response = $this->getJson('/api/v1/products?styles[]=goth_alt&styles[]=boho');

        $response->assertStatus(200);
        $titles = collect($response->json('data'))->pluck('title');
        $this->assertEqualsCanonicalizing(['Goth item', 'Boho item'], $titles->all());
    }

    // ─── Search intents ───────────────────────────────────────────────────────

    public function test_style_token_surfaces_tagged_products(): void
    {
        Product::factory()->create(['title' => 'Crna suknja', 'styles' => ['goth_alt']]);
        Product::factory()->create(['title' => 'Bijela suknja', 'styles' => null]);

        $response = $this->getJson('/api/v1/products?q=goth');

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertContains('Crna suknja', $titles);
        $this->assertNotContains('Bijela suknja', $titles);
    }

    public function test_vintage_token_surfaces_retro_style_and_vintage_badge(): void
    {
        Product::factory()->create(['title' => 'Retro jakna', 'styles' => ['retro']]);
        Product::factory()->create(['title' => 'Verified jakna', 'vintage_status' => 'approved']);
        Product::factory()->create(['title' => 'Obicna jakna']);

        $response = $this->getJson('/api/v1/products?q=vintage');

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertContains('Retro jakna', $titles);
        $this->assertContains('Verified jakna', $titles);
        $this->assertNotContains('Obicna jakna', $titles);
    }

    // ─── Preferences / personalized feed ─────────────────────────────────────

    public function test_style_preference_is_saved_and_returned(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/v1/users/me/preferences', ['styles' => ['y2k', 'grunge']])
            ->assertStatus(200)
            ->assertJsonPath('data.styles', ['y2k', 'grunge']);
    }

    public function test_invalid_style_preference_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/v1/users/me/preferences', ['styles' => ['not_a_style']])
            ->assertStatus(422);
    }

    public function test_personalized_feed_matches_style_preference(): void
    {
        $user = User::factory()->create();
        $user->preference()->create(['styles' => ['y2k']]);

        Product::factory()->create(['title' => 'Y2K top', 'styles' => ['y2k'], 'size' => 'XL']);
        Product::factory()->create(['title' => 'Plain top', 'styles' => null, 'size' => 'XL']);

        $response = $this->actingAs($user)->getJson('/api/v1/products?personalized=true');

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertContains('Y2K top', $titles);
        $this->assertNotContains('Plain top', $titles);
    }

    // ─── Founding seller flag ─────────────────────────────────────────────────

    public function test_founding_seller_flag_is_exposed_on_user_resource(): void
    {
        $founding = User::factory()->create(['is_founding_seller' => true]);
        Product::factory()->create(['seller_id' => $founding->id, 'title' => 'Founding item']);

        $response = $this->getJson('/api/v1/products?q=Founding');

        $this->assertTrue($response->json('data.0.seller.isFoundingSeller'));
    }
}
