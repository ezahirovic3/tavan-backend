<?php

namespace Tests\Feature\Products;

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignerBadgeTest extends TestCase
{
    use RefreshDatabase;

    // ─── Reseller application (per-listing, applyDesigner) ─────────────────────

    public function test_regular_seller_designer_application_stays_pending(): void
    {
        $seller  = User::factory()->create();
        $product = Product::factory()->create(['seller_id' => $seller->id]);

        $response = $this->actingAs($seller)
            ->postJson("/api/v1/products/{$product->id}/designer", [
                'brand' => 'Salvatore Ferragamo',
                'notes' => 'Original box i certifikat.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.designerStatus', 'pending');

        $this->assertDatabaseHas('products', [
            'id'                  => $product->id,
            'designer_status'     => 'pending',
            'designer_reviewed_by'=> null,
        ]);
    }

    public function test_designer_reseller_application_is_auto_approved(): void
    {
        $seller  = User::factory()->create(['is_designer_reseller' => true]);
        $product = Product::factory()->create(['seller_id' => $seller->id]);

        $response = $this->actingAs($seller)
            ->postJson("/api/v1/products/{$product->id}/designer", [
                'brand' => 'Salvatore Ferragamo',
                'notes' => 'Original box i certifikat.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.designerStatus', 'approved')
            ->assertJsonPath('data.designer.brand', 'Salvatore Ferragamo');

        $this->assertDatabaseHas('products', [
            'id'                   => $product->id,
            'designer_status'      => 'approved',
            'designer_reviewed_by' => $seller->id,
        ]);
    }

    public function test_designer_maker_flag_does_not_auto_approve_existing_pending_applications(): void
    {
        // A designer_maker seller applying explicitly still goes through applyDesigner(),
        // which only auto-approves is_designer_reseller — makers get tagged at creation instead.
        $seller  = User::factory()->create(['is_designer_maker' => true]);
        $product = Product::factory()->create(['seller_id' => $seller->id]);

        $response = $this->actingAs($seller)
            ->postJson("/api/v1/products/{$product->id}/designer", [
                'brand' => 'azure_jewels',
                'notes' => 'Handmade.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.designerStatus', 'pending');
    }

    // ─── Maker auto-tagging at listing creation ─────────────────────────────────

    public function test_designer_maker_listing_is_auto_tagged_as_designer(): void
    {
        $seller = User::factory()->create([
            'is_designer_maker' => true,
            'name'              => 'Azure Jewels',
        ]);
        $brand = Brand::factory()->create();

        $response = $this->actingAs($seller)->postJson('/api/v1/products', [
            'title'         => 'Handmade prsten',
            'price'         => 40.00,
            'root_category' => 'women',
            'category'      => 'accessories',
            'condition'     => 'new',
            'shipping_size' => 'S',
            'location'      => 'Sarajevo',
            'brand_id'      => $brand->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.designerStatus', 'approved')
            ->assertJsonPath('data.designer.brand', 'Azure Jewels');

        $this->assertDatabaseHas('products', [
            'seller_id'            => $seller->id,
            'designer_status'      => 'approved',
            'designer_brand'       => 'Azure Jewels',
            'designer_reviewed_by' => $seller->id,
        ]);
    }

    public function test_designer_maker_draft_listing_is_also_auto_tagged(): void
    {
        $seller = User::factory()->create(['is_designer_maker' => true]);

        $response = $this->actingAs($seller)->postJson('/api/v1/products', [
            'status' => 'draft',
            'title'  => 'Draft prsten',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.designerStatus', 'approved');
    }

    public function test_regular_seller_listing_is_not_auto_tagged_as_designer(): void
    {
        $seller = User::factory()->create();
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
            ->assertJsonPath('data.designerStatus', null);
    }

    // ─── Flags exposed on the user resource ─────────────────────────────────────

    public function test_designer_flags_are_exposed_on_user_resource(): void
    {
        $seller = User::factory()->create([
            'is_designer_reseller' => true,
            'is_designer_maker'    => false,
        ]);

        $this->getJson("/api/v1/users/{$seller->id}")
            ->assertJsonPath('data.isDesignerReseller', true)
            ->assertJsonPath('data.isDesignerMaker', false);
    }
}
