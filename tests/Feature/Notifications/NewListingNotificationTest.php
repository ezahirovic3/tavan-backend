<?php

namespace Tests\Feature\Notifications;

use App\Jobs\NotifyFollowersNewListings;
use App\Models\Follow;
use App\Models\Product;
use App\Models\User;
use App\Notifications\UserActivityNotification;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Covers the 30-min-after-last-listing batching in
 * App\Console\Commands\NotifyFollowersOfNewListingsCommand and the fan-out
 * in App\Jobs\NotifyFollowersNewListings.
 */
class NewListingNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function ageProduct(Product $product, int $minutesOld): Product
    {
        $product->forceFill(['created_at' => now()->subMinutes($minutesOld)])->save();

        return $product->fresh();
    }

    public function test_a_recent_listing_is_not_yet_notified(): void
    {
        Queue::fake();
        $seller = User::factory()->create();
        Follow::create(['follower_id' => User::factory()->create()->id, 'followed_id' => $seller->id]);
        Product::factory()->create(['seller_id' => $seller->id, 'status' => 'active']);

        $this->artisan('follows:notify-new-listings')->assertSuccessful();

        Queue::assertNotPushed(NotifyFollowersNewListings::class);
        $this->assertNull(Product::first()->followers_notified_at);
    }

    public function test_a_listing_past_the_quiet_window_is_notified(): void
    {
        Queue::fake();
        $seller = User::factory()->create();
        Follow::create(['follower_id' => User::factory()->create()->id, 'followed_id' => $seller->id]);
        $product = $this->ageProduct(
            Product::factory()->create(['seller_id' => $seller->id, 'status' => 'active']),
            35,
        );

        $this->artisan('follows:notify-new-listings')->assertSuccessful();

        Queue::assertPushed(
            NotifyFollowersNewListings::class,
            fn ($job) => $this->jobProductIds($job) === [$product->id],
        );
        $this->assertNotNull($product->fresh()->followers_notified_at);
    }

    public function test_batch_waits_for_the_most_recent_listing_in_the_group(): void
    {
        Queue::fake();
        $seller = User::factory()->create();
        Follow::create(['follower_id' => User::factory()->create()->id, 'followed_id' => $seller->id]);

        $old = $this->ageProduct(Product::factory()->create(['seller_id' => $seller->id, 'status' => 'active']), 40);
        $recent = $this->ageProduct(Product::factory()->create(['seller_id' => $seller->id, 'status' => 'active']), 10);

        // The 10-min-old listing keeps the whole batch pending.
        $this->artisan('follows:notify-new-listings')->assertSuccessful();
        Queue::assertNotPushed(NotifyFollowersNewListings::class);
        $this->assertNull($old->fresh()->followers_notified_at);
        $this->assertNull($recent->fresh()->followers_notified_at);

        // Once the newest listing also clears the window, both fire together.
        $this->ageProduct($recent, 35);
        $this->artisan('follows:notify-new-listings')->assertSuccessful();

        Queue::assertPushed(
            NotifyFollowersNewListings::class,
            fn ($job) => count($this->jobProductIds($job)) === 2
                && in_array($old->id, $this->jobProductIds($job), true)
                && in_array($recent->id, $this->jobProductIds($job), true),
        );
    }

    public function test_a_seller_with_no_followers_is_marked_notified_but_nothing_is_dispatched(): void
    {
        Queue::fake();
        $seller = User::factory()->create();
        $product = $this->ageProduct(
            Product::factory()->create(['seller_id' => $seller->id, 'status' => 'active']),
            35,
        );

        $this->artisan('follows:notify-new-listings')->assertSuccessful();

        Queue::assertNotPushed(NotifyFollowersNewListings::class);
        $this->assertNotNull($product->fresh()->followers_notified_at);
    }

    public function test_rerunning_the_command_does_not_renotify(): void
    {
        Queue::fake();
        $seller = User::factory()->create();
        Follow::create(['follower_id' => User::factory()->create()->id, 'followed_id' => $seller->id]);
        $this->ageProduct(Product::factory()->create(['seller_id' => $seller->id, 'status' => 'active']), 35);

        $this->artisan('follows:notify-new-listings')->assertSuccessful();
        $this->artisan('follows:notify-new-listings')->assertSuccessful();

        Queue::assertPushedTimes(NotifyFollowersNewListings::class, 1);
    }

    public function test_a_product_already_marked_notified_is_never_repicked_up(): void
    {
        Queue::fake();
        $seller = User::factory()->create();
        Follow::create(['follower_id' => User::factory()->create()->id, 'followed_id' => $seller->id]);
        $product = $this->ageProduct(Product::factory()->create(['seller_id' => $seller->id, 'status' => 'active']), 60);
        $product->forceFill(['followers_notified_at' => now()->subMinutes(50)])->save();

        $this->artisan('follows:notify-new-listings')->assertSuccessful();

        Queue::assertNotPushed(NotifyFollowersNewListings::class);
    }

    public function test_job_sends_singular_copy_for_one_listing(): void
    {
        Notification::fake();
        $seller = User::factory()->create(['name' => 'Ana Marić']);
        $follower = User::factory()->create();
        Follow::create(['follower_id' => $follower->id, 'followed_id' => $seller->id]);
        $product = Product::factory()->create(['seller_id' => $seller->id, 'status' => 'active', 'title' => 'Vintage jakna']);

        (new NotifyFollowersNewListings($seller->id, [$product->id]))->handle(app(PushNotificationService::class));

        Notification::assertSentTo(
            $follower,
            UserActivityNotification::class,
            function (UserActivityNotification $n) use ($product, $seller, $follower) {
                $data = $n->toDatabase($follower);

                return $data['type'] === 'new_listing'
                    && $data['title'] === 'Novi artikal 🆕'
                    && str_contains($data['body'], 'Vintage jakna')
                    && $data['meta']['productId'] === $product->id
                    && $data['meta']['sellerId'] === $seller->id
                    && ! isset($data['meta']['count']);
            },
        );
    }

    public function test_job_sends_plural_copy_for_multiple_listings(): void
    {
        Notification::fake();
        $seller = User::factory()->create();
        $follower = User::factory()->create();
        Follow::create(['follower_id' => $follower->id, 'followed_id' => $seller->id]);
        $products = Product::factory()->count(3)->create(['seller_id' => $seller->id, 'status' => 'active']);

        (new NotifyFollowersNewListings($seller->id, $products->pluck('id')->all()))
            ->handle(app(PushNotificationService::class));

        Notification::assertSentTo(
            $follower,
            UserActivityNotification::class,
            function (UserActivityNotification $n) use ($seller, $follower) {
                $data = $n->toDatabase($follower);

                return $data['type'] === 'new_listing'
                    && $data['title'] === 'Novi artikli 🆕'
                    && $data['meta']['sellerId'] === $seller->id
                    && $data['meta']['count'] === 3;
            },
        );
    }

    public function test_job_notifies_every_follower(): void
    {
        Notification::fake();
        $seller = User::factory()->create();
        $a = User::factory()->create();
        $b = User::factory()->create();
        Follow::create(['follower_id' => $a->id, 'followed_id' => $seller->id]);
        Follow::create(['follower_id' => $b->id, 'followed_id' => $seller->id]);
        $product = Product::factory()->create(['seller_id' => $seller->id, 'status' => 'active']);

        (new NotifyFollowersNewListings($seller->id, [$product->id]))->handle(app(PushNotificationService::class));

        Notification::assertSentTo([$a, $b], UserActivityNotification::class);
    }

    /**
     * @return string[]
     */
    private function jobProductIds(NotifyFollowersNewListings $job): array
    {
        $ref = new \ReflectionClass($job);
        $prop = $ref->getProperty('productIds');
        $prop->setAccessible(true);

        return $prop->getValue($job);
    }
}
