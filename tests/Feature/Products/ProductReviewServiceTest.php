<?php

namespace Tests\Feature\Products;

use App\Models\Message;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    // The system support user (sender of support replies) is created by the
    // 2026_05_09_000005_create_system_support_user migration, so RefreshDatabase
    // already provides it.

    public function test_approve_publishes_a_pending_listing(): void
    {
        $admin  = User::factory()->create();
        $seller = User::factory()->create(['listings_require_review' => false]);
        $product = Product::factory()->for($seller, 'seller')->create(['status' => 'pending_review']);

        $sellerGateLifted = app(ProductReviewService::class)->approve($product, $admin);

        $this->assertFalse($sellerGateLifted);
        $this->assertSame('active', $product->fresh()->status);
    }

    public function test_approve_lifts_the_seller_review_gate_and_notifies_once(): void
    {
        $admin  = User::factory()->create();
        $seller = User::factory()->create(['listings_require_review' => true]);
        $service = app(ProductReviewService::class);

        $first  = Product::factory()->for($seller, 'seller')->create(['status' => 'pending_review']);
        $second = Product::factory()->for($seller, 'seller')->create(['status' => 'pending_review']);

        $this->assertTrue($service->approve($first, $admin));
        $this->assertFalse($seller->fresh()->listings_require_review);

        // Second listing of the same seller: still approved, but no repeat gate/notify.
        $this->assertFalse($service->approve($second, $admin));
        $this->assertSame('active', $second->fresh()->status);

        // Exactly one "you're approved" support message was sent.
        $this->assertSame(1, Message::count());
    }

    public function test_approve_ignores_a_listing_that_is_not_pending_review(): void
    {
        $admin  = User::factory()->create();
        $product = Product::factory()->create(['status' => 'active']);

        $this->assertFalse(app(ProductReviewService::class)->approve($product, $admin));
    }

    public function test_reject_sends_listing_to_draft_and_messages_the_seller_with_the_reason(): void
    {
        $admin  = User::factory()->create();
        $seller = User::factory()->create();
        $product = Product::factory()->for($seller, 'seller')
            ->create(['status' => 'pending_review', 'title' => 'Kožna jakna']);

        app(ProductReviewService::class)->reject($product, $admin, 'Fotografije nisu stvarne.');

        $this->assertSame('draft', $product->fresh()->status);

        $message = Message::latest('id')->first();
        $this->assertNotNull($message);
        $this->assertStringContainsString('Kožna jakna', $message->body);
        $this->assertStringContainsString('Fotografije nisu stvarne.', $message->body);
    }
}
