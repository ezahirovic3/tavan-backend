<?php

namespace Tests\Feature\Products;

use App\Models\ListingRefresh;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * "Osvježi oglas" — the free renewal for stale listings.
 *
 * Note on fixtures: ProductObserver stamps published_at on the first save of an
 * active listing, so a factory product is always "published now". Tests that
 * need an old listing set published_at explicitly — the observer's null-guard
 * leaves an explicit value alone.
 */
class ListingRefreshTest extends TestCase
{
    use RefreshDatabase;

    private function staleProduct(User $seller, int $daysOld = 40): Product
    {
        return Product::factory()->create([
            'seller_id'    => $seller->id,
            'status'       => 'active',
            'created_at'   => now()->subDays($daysOld),
            'published_at' => now()->subDays($daysOld),
        ]);
    }

    // ─── Happy path ──────────────────────────────────────────────────────────

    public function test_owner_can_refresh_a_stale_active_listing(): void
    {
        $seller  = User::factory()->create();
        $product = $this->staleProduct($seller);

        $response = $this->actingAs($seller)
            ->postJson("/api/v1/products/{$product->id}/refresh");

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.refreshedAt'));

        // Immediately back on cooldown.
        $this->assertFalse($response->json('data.canRefresh'));
        $this->assertSame('too_recent', $response->json('data.refreshBlockedBy'));
    }

    public function test_refresh_moves_a_listing_up_the_newest_feed(): void
    {
        $seller = User::factory()->create();
        $old    = $this->staleProduct($seller, 40);
        $newer  = $this->staleProduct($seller, 2);

        $before = $this->getJson('/api/v1/products?sort_by=newest')->json('data.*.id');
        $this->assertSame([$newer->id, $old->id], $before);

        $this->actingAs($seller)->postJson("/api/v1/products/{$old->id}/refresh")
            ->assertStatus(200);

        $after = $this->getJson('/api/v1/products?sort_by=newest')->json('data.*.id');
        $this->assertSame([$old->id, $newer->id], $after);
    }

    public function test_default_sort_matches_explicit_newest(): void
    {
        $seller = User::factory()->create();
        $this->staleProduct($seller, 40);
        $this->staleProduct($seller, 2);

        $this->assertSame(
            $this->getJson('/api/v1/products?sort_by=newest')->json('data.*.id'),
            $this->getJson('/api/v1/products')->json('data.*.id'),
        );
    }

    public function test_refresh_does_not_touch_updated_at_or_write_an_activity_log(): void
    {
        $seller  = User::factory()->create();
        $product = $this->staleProduct($seller);

        $originalUpdatedAt = $product->fresh()->updated_at;
        $activityBefore    = Activity::where('subject_id', $product->id)->count();

        $this->travel(1)->hours();

        $this->actingAs($seller)->postJson("/api/v1/products/{$product->id}/refresh")
            ->assertStatus(200);

        $this->assertEquals(
            $originalUpdatedAt->timestamp,
            $product->fresh()->updated_at->timestamp,
            'refresh must not bump updated_at',
        );
        $this->assertSame(
            $activityBefore,
            Activity::where('subject_id', $product->id)->count(),
            'refresh must not write an activity log entry',
        );
    }

    public function test_refresh_is_logged(): void
    {
        $seller  = User::factory()->create();
        $product = $this->staleProduct($seller);

        $this->actingAs($seller)->postJson("/api/v1/products/{$product->id}/refresh")
            ->assertStatus(200);

        $this->assertDatabaseHas('listing_refreshes', [
            'user_id'    => $seller->id,
            'product_id' => $product->id,
        ]);
    }

    // ─── The 30-day rule ─────────────────────────────────────────────────────

    public function test_a_listing_published_too_recently_cannot_be_refreshed(): void
    {
        $seller  = User::factory()->create();
        $product = $this->staleProduct($seller, 10);

        $response = $this->actingAs($seller)
            ->postJson("/api/v1/products/{$product->id}/refresh");

        $response->assertStatus(429)
            ->assertJsonPath('code', 'refresh_too_recent')
            // ConvertResponseKeysToCamelCase rewrites the body keys on the way out.
            ->assertJsonStructure(['message', 'code', 'retryAfter', 'nextEligibleAt']);

        $this->assertIsInt($response->json('retryAfter'));
        $this->assertNotNull($response->headers->get('Retry-After'));
    }

    public function test_a_recently_refreshed_listing_cannot_be_refreshed_again(): void
    {
        $seller  = User::factory()->create();
        $product = $this->staleProduct($seller, 90);
        $product->forceFill(['refreshed_at' => now()->subDays(5)])->saveQuietly();

        $this->actingAs($seller)->postJson("/api/v1/products/{$product->id}/refresh")
            ->assertStatus(429)
            ->assertJsonPath('code', 'refresh_too_recent');
    }

    public function test_a_listing_refreshed_long_ago_can_be_refreshed_again(): void
    {
        $seller  = User::factory()->create();
        $product = $this->staleProduct($seller, 90);
        $product->forceFill(['refreshed_at' => now()->subDays(40)])->saveQuietly();

        $this->actingAs($seller)->postJson("/api/v1/products/{$product->id}/refresh")
            ->assertStatus(200);
    }

    public function test_eligibility_is_measured_from_publication_not_creation(): void
    {
        // Sat 35 days in review, went live 5 days ago: not refreshable yet.
        $seller  = User::factory()->create();
        $product = Product::factory()->create([
            'seller_id'    => $seller->id,
            'status'       => 'active',
            'created_at'   => now()->subDays(40),
            'published_at' => now()->subDays(5),
        ]);

        $this->actingAs($seller)->postJson("/api/v1/products/{$product->id}/refresh")
            ->assertStatus(429)
            ->assertJsonPath('code', 'refresh_too_recent');
    }

    public function test_minimum_age_is_configurable(): void
    {
        config(['tavan.refresh_min_age_days' => 3]);

        $seller  = User::factory()->create();
        $product = $this->staleProduct($seller, 5);

        $this->actingAs($seller)->postJson("/api/v1/products/{$product->id}/refresh")
            ->assertStatus(200);
    }

    // ─── Deliberately unmetered ──────────────────────────────────────────────

    public function test_there_is_no_per_seller_quota(): void
    {
        $seller = User::factory()->create();

        // Refresh is inventory hygiene, not a visibility perk — capping how often
        // sellers confirm their stock is live would produce MORE stale listings.
        // If this fails, a quota was reintroduced; see the plan's Guardrails.
        foreach (range(1, 10) as $i) {
            $product = $this->staleProduct($seller);

            $this->actingAs($seller)
                ->postJson("/api/v1/products/{$product->id}/refresh")
                ->assertStatus(200, "refresh #{$i} should not be rate-limited");
        }

        $this->assertSame(10, ListingRefresh::where('user_id', $seller->id)->count());
    }

    // ─── Authorization and state ─────────────────────────────────────────────

    public function test_a_non_owner_cannot_refresh_someone_elses_listing(): void
    {
        $product  = $this->staleProduct(User::factory()->create());
        $attacker = User::factory()->create();

        $this->actingAs($attacker)
            ->postJson("/api/v1/products/{$product->id}/refresh")
            ->assertStatus(403);
    }

    public function test_guests_cannot_refresh(): void
    {
        $product = $this->staleProduct(User::factory()->create());

        $this->postJson("/api/v1/products/{$product->id}/refresh")
            ->assertStatus(401);
    }

    public function test_refresh_requires_a_verified_phone(): void
    {
        $seller  = User::factory()->phoneUnverified()->create();
        $product = $this->staleProduct($seller);

        $this->actingAs($seller)
            ->postJson("/api/v1/products/{$product->id}/refresh")
            ->assertStatus(403)
            ->assertJsonPath('code', 'phone_unverified');
    }

    #[DataProvider('nonActiveStatuses')]
    public function test_only_active_listings_can_be_refreshed(string $status): void
    {
        $seller  = User::factory()->create();
        $product = Product::factory()->create([
            'seller_id'    => $seller->id,
            'status'       => $status,
            'created_at'   => now()->subDays(40),
            'published_at' => now()->subDays(40),
        ]);

        $this->actingAs($seller)
            ->postJson("/api/v1/products/{$product->id}/refresh")
            ->assertStatus(422)
            ->assertJsonPath('code', 'refresh_not_eligible');
    }

    public static function nonActiveStatuses(): array
    {
        return [
            'draft'          => ['draft'],
            'pending review' => ['pending_review'],
            'reserved'       => ['reserved'],
            'sold'           => ['sold'],
        ];
    }

    // ─── Resource shape ──────────────────────────────────────────────────────

    public function test_refresh_state_is_owner_only(): void
    {
        $seller  = User::factory()->create();
        $product = $this->staleProduct($seller);

        $this->actingAs($seller)->getJson("/api/v1/products/{$product->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.canRefresh', true)
            ->assertJsonStructure(['data' => ['canRefresh', 'refreshableAt', 'refreshBlockedBy']]);

        $this->actingAs(User::factory()->create())
            ->getJson("/api/v1/products/{$product->id}")
            ->assertStatus(200)
            ->assertJsonMissingPath('data.canRefresh')
            ->assertJsonMissingPath('data.refreshableAt');
    }

    public function test_refresh_state_is_exposed_on_the_sellers_own_listings(): void
    {
        // The Seller Center banner filters this client-side, so the field has to
        // survive the /users/{username}/products route's optional-auth resolution.
        $seller = User::factory()->create();
        $this->staleProduct($seller);

        $this->actingAs($seller)
            ->getJson("/api/v1/users/{$seller->id}/products")
            ->assertStatus(200)
            ->assertJsonPath('data.0.canRefresh', true);
    }

    // ─── Regression: the workaround this replaces ────────────────────────────

    public function test_editing_a_listing_no_longer_refloats_it(): void
    {
        $seller = User::factory()->create();
        $old    = $this->staleProduct($seller, 40);
        $newer  = $this->staleProduct($seller, 2);

        $this->actingAs($seller)
            ->patchJson("/api/v1/products/{$old->id}", ['title' => 'Novi naslov'])
            ->assertStatus(200);

        $this->assertSame(
            [$newer->id, $old->id],
            $this->getJson('/api/v1/products')->json('data.*.id'),
            'editing must not bump a listing — that is what refresh is for',
        );
    }
}
