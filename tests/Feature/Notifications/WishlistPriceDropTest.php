<?php

namespace Tests\Feature\Notifications;

use App\Jobs\NotifyWishlistPriceDrop;
use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use App\Notifications\UserActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WishlistPriceDropTest extends TestCase
{
    use RefreshDatabase;

    private function wishlistedProduct(float $price): Product
    {
        $product = Product::factory()->create(['status' => 'active', 'price' => $price]);
        $wishlister = User::factory()->create();
        WishlistItem::create(['user_id' => $wishlister->id, 'product_id' => $product->id]);

        return $product;
    }

    public function test_a_drop_below_threshold_does_not_dispatch_the_job(): void
    {
        Queue::fake();

        $product = $this->wishlistedProduct(100);
        // 2% / 2 KM drop — below the default 5%/5 KM thresholds
        $product->update(['price' => 98]);

        Queue::assertNotPushed(NotifyWishlistPriceDrop::class);
    }

    public function test_a_drop_clearing_the_threshold_dispatches_the_job(): void
    {
        Queue::fake();

        $product = $this->wishlistedProduct(100);
        $product->update(['price' => 90]);

        Queue::assertPushed(NotifyWishlistPriceDrop::class, fn ($job) => $this->jobMatches($job, $product->id));
    }

    public function test_a_price_increase_never_dispatches(): void
    {
        Queue::fake();

        $product = $this->wishlistedProduct(100);
        $product->update(['price' => 150]);

        Queue::assertNotPushed(NotifyWishlistPriceDrop::class);
    }

    public function test_repeated_drops_within_the_cooldown_only_fire_once(): void
    {
        Queue::fake();

        $product = $this->wishlistedProduct(100);
        $product->update(['price' => 90]); // fires
        $product->update(['price' => 80]); // still within cooldown — suppressed

        Queue::assertPushedTimes(NotifyWishlistPriceDrop::class, 1);
    }

    public function test_a_sold_product_is_not_notified_when_the_job_runs(): void
    {
        Notification::fake();
        // Flipping status to 'sold' below incidentally fires
        // NotifyWishlistItemSold via ProductObserver — unrelated to what
        // this test checks, but on the sync queue connection it'd run
        // immediately and pollute the Notification::fake() log this test
        // asserts against. Fake the queue too so it's captured, not run.
        Queue::fake();

        $product = Product::factory()->create(['status' => 'active', 'price' => 100]);
        $wishlister = User::factory()->create();
        WishlistItem::create(['user_id' => $wishlister->id, 'product_id' => $product->id]);

        $product->update(['status' => 'sold']);

        (new NotifyWishlistPriceDrop($product->id, 100, 80))->handle(app(\App\Services\PushNotificationService::class));

        Notification::assertNothingSent();
    }

    public function test_the_job_notifies_every_wishlister(): void
    {
        Notification::fake();

        $product = Product::factory()->create(['status' => 'active', 'price' => 80]);
        $a = User::factory()->create();
        $b = User::factory()->create();
        WishlistItem::create(['user_id' => $a->id, 'product_id' => $product->id]);
        WishlistItem::create(['user_id' => $b->id, 'product_id' => $product->id]);

        (new NotifyWishlistPriceDrop($product->id, 100, 80))->handle(app(\App\Services\PushNotificationService::class));

        Notification::assertSentTo([$a, $b], UserActivityNotification::class);
    }

    private function jobMatches(NotifyWishlistPriceDrop $job, string $productId): bool
    {
        $ref = new \ReflectionClass($job);
        $prop = $ref->getProperty('productId');
        $prop->setAccessible(true);

        return $prop->getValue($job) === $productId;
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
