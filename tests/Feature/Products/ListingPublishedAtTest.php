<?php

namespace Tests\Feature\Products;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * products.published_at — when a listing first became visible to buyers.
 *
 * The feed's recency axis is COALESCE(refreshed_at, published_at, created_at),
 * so this column is what keeps an admin-approved listing debuting at the top
 * instead of already buried at its draft age. ProductObserver::saving() stamps
 * it once and never overwrites it, which is also what stops hide -> unhide
 * being used as a free refresh.
 */
class ListingPublishedAtTest extends TestCase
{
    use RefreshDatabase;

    private function feedIds(): array
    {
        return $this->getJson('/api/v1/products')->json('data.*.id');
    }

    // ─── Stamping ────────────────────────────────────────────────────────────

    public function test_approving_a_pending_listing_stamps_published_at_and_floats_it(): void
    {
        $seller = User::factory()->create();

        // Submitted 5 days ago, still awaiting review.
        $awaiting = Product::factory()->create([
            'seller_id'    => $seller->id,
            'status'       => 'pending_review',
            'created_at'   => now()->subDays(5),
            'published_at' => null,
        ]);

        // Meanwhile someone else listed 2 days ago and went live immediately.
        $rival = Product::factory()->create([
            'status'       => 'active',
            'created_at'   => now()->subDays(2),
            'published_at' => now()->subDays(2),
        ]);

        // Admin approves.
        $awaiting->update(['status' => 'active']);

        $this->assertNotNull($awaiting->fresh()->published_at);
        $this->assertTrue(
            $awaiting->fresh()->published_at->isAfter(now()->subMinute()),
            'approval should stamp published_at at approval time, not creation time',
        );

        $this->assertSame(
            [$awaiting->id, $rival->id],
            $this->feedIds(),
            'an approved listing should debut at the top of the feed',
        );
    }

    public function test_publishing_without_review_stamps_published_at(): void
    {
        $seller  = User::factory()->create(['listings_require_review' => false]);
        $product = Product::factory()->create([
            'seller_id'    => $seller->id,
            'status'       => 'draft',
            'published_at' => null,
        ]);
        ProductImage::factory()->create(['product_id' => $product->id]);

        $this->actingAs($seller)
            ->postJson("/api/v1/products/{$product->id}/publish")
            ->assertStatus(200);

        $product->refresh();
        $this->assertSame('active', $product->status);
        $this->assertNotNull($product->published_at);
    }

    public function test_a_draft_has_no_published_at(): void
    {
        $product = Product::factory()->create(['status' => 'draft', 'published_at' => null]);

        $this->assertNull($product->fresh()->published_at);
    }

    // ─── Stamped exactly once ────────────────────────────────────────────────

    public function test_hiding_and_unhiding_does_not_refloat_a_listing(): void
    {
        $seller = User::factory()->create();

        $old = Product::factory()->create([
            'seller_id'    => $seller->id,
            'status'       => 'active',
            'created_at'   => now()->subDays(40),
            'published_at' => now()->subDays(40),
        ]);
        $newer = Product::factory()->create([
            'status'       => 'active',
            'created_at'   => now()->subDays(2),
            'published_at' => now()->subDays(2),
        ]);

        $originalPublishedAt = $old->published_at;

        // Hide, then unhide — the loophole this guards against.
        $old->update(['status' => 'draft']);
        $old->update(['status' => 'active']);

        $this->assertEquals(
            $originalPublishedAt->timestamp,
            $old->fresh()->published_at->timestamp,
            'published_at must be stamped once and never overwritten',
        );
        $this->assertSame([$newer->id, $old->id], $this->feedIds());
    }

    public function test_returning_from_reserved_to_active_does_not_restamp(): void
    {
        $product = Product::factory()->create([
            'status'       => 'reserved',
            'created_at'   => now()->subDays(20),
            'published_at' => now()->subDays(20),
        ]);
        $original = $product->published_at;

        $product->update(['status' => 'active']);

        $this->assertEquals($original->timestamp, $product->fresh()->published_at->timestamp);
    }

    public function test_an_ordinary_edit_does_not_restamp_or_refloat(): void
    {
        $seller = User::factory()->create();

        $old = Product::factory()->create([
            'seller_id'    => $seller->id,
            'status'       => 'active',
            'created_at'   => now()->subDays(40),
            'published_at' => now()->subDays(40),
        ]);
        $newer = Product::factory()->create([
            'status'       => 'active',
            'created_at'   => now()->subDays(2),
            'published_at' => now()->subDays(2),
        ]);
        $original = $old->published_at;

        $this->actingAs($seller)
            ->patchJson("/api/v1/products/{$old->id}", ['title' => 'Novi naslov'])
            ->assertStatus(200);

        $this->assertEquals($original->timestamp, $old->fresh()->published_at->timestamp);
        $this->assertSame([$newer->id, $old->id], $this->feedIds());
    }

    public function test_applying_for_a_badge_does_not_refloat_a_live_listing(): void
    {
        $seller = User::factory()->create();

        $old = Product::factory()->create([
            'seller_id'    => $seller->id,
            'status'       => 'active',
            'created_at'   => now()->subDays(40),
            'published_at' => now()->subDays(40),
        ]);
        $newer = Product::factory()->create([
            'status'       => 'active',
            'created_at'   => now()->subDays(2),
            'published_at' => now()->subDays(2),
        ]);

        $this->actingAs($seller)
            ->postJson("/api/v1/products/{$old->id}/vintage", [
                'era'   => '90s',
                'notes' => 'Original iz devedesetih.',
            ])
            ->assertStatus(200);

        $this->assertSame(
            [$newer->id, $old->id],
            $this->feedIds(),
            'badge review is not a seller edit and must not move the listing',
        );
    }
}
