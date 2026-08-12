<?php

namespace Tests\Feature\Notifications;

use App\Models\PushToken;
use App\Models\User;
use App\Notifications\UserActivityNotification;
use App\Services\PushNotificationService;
use App\Services\UserNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The base TestCase mocks PushNotificationService entirely
        // (shouldIgnoreMissing) so no test accidentally hits the real Expo
        // API. These tests are specifically about that service's own
        // enforcement logic, so undo the mock and fake the HTTP layer
        // underneath it instead.
        $this->app->forgetInstance(PushNotificationService::class);
        Http::fake(['exp.host/*' => Http::response(['data' => []], 200)]);
    }

    private function userWithToken(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        PushToken::create(['user_id' => $user->id, 'token' => 'ExponentPushToken[' . $user->id . ']', 'platform' => 'ios']);

        return $user;
    }

    public function test_master_switch_off_blocks_the_push(): void
    {
        $user = $this->userWithToken(['notifications_enabled' => false]);

        app(PushNotificationService::class)->sendToUser($user->id, 'Naslov', 'Tekst', ['type' => 'order']);

        Http::assertNothingSent();
    }

    public function test_muted_category_blocks_the_push_but_not_the_in_app_notification(): void
    {
        $user = $this->userWithToken(['notify_orders' => false]);

        app(PushNotificationService::class)->sendToUser($user->id, 'Narudžba prihvaćena', 'Tekst', ['type' => 'order', 'orderId' => '123']);
        Http::assertNothingSent();

        app(UserNotificationService::class)->record($user, 'order', 'Narudžba prihvaćena', 'Tekst', ['orderId' => '123']);

        $this->assertCount(1, $user->fresh()->notifications);
    }

    public function test_other_categories_still_send_when_only_orders_is_muted(): void
    {
        $user = $this->userWithToken(['notify_orders' => false]);

        app(PushNotificationService::class)->sendToUser($user->id, 'Nova recenzija', 'Tekst', ['type' => 'review']);

        Http::assertSentCount(1);
    }

    public function test_unmapped_type_fails_open_and_still_sends(): void
    {
        $user = $this->userWithToken(['notify_orders' => false, 'notify_activity' => false]);

        app(PushNotificationService::class)->sendToUser($user->id, 'Naslov', 'Tekst', ['type' => 'some_future_type']);

        Http::assertSentCount(1);
    }

    public function test_bulk_send_filters_out_muted_recipients(): void
    {
        $optedIn = $this->userWithToken();
        $mutedCategory = $this->userWithToken(['notify_announcements' => false]);
        $mutedMaster = $this->userWithToken(['notifications_enabled' => false]);

        app(PushNotificationService::class)->sendToUsers(
            [$optedIn->id, $mutedCategory->id, $mutedMaster->id],
            'Tavan',
            'Novost',
            ['type' => 'announcement'],
        );

        Http::assertSentCount(1);
    }

    public function test_notification_list_endpoints(): void
    {
        $user = User::factory()->create();

        $user->notify(new UserActivityNotification('order', 'Narudžba prihvaćena', 'Tekst', ['orderId' => '1']));
        $user->notify(new UserActivityNotification('price_drop', 'Cijena je pala', 'Tekst', ['productId' => '2']));

        $unread = $this->actingAs($user)->getJson('/api/v1/notifications/unread-count');
        $unread->assertOk()->assertJson(['data' => ['count' => 2]]);

        $index = $this->actingAs($user)->getJson('/api/v1/notifications');
        $index->assertOk();
        $this->assertCount(2, $index->json('data'));

        $firstId = $index->json('data.0.id');
        $this->actingAs($user)->postJson("/api/v1/notifications/{$firstId}/read")->assertOk();

        $this->actingAs($user)->getJson('/api/v1/notifications/unread-count')
            ->assertJson(['data' => ['count' => 1]]);

        $this->actingAs($user)->postJson('/api/v1/notifications/read-all')->assertOk();

        $this->actingAs($user)->getJson('/api/v1/notifications/unread-count')
            ->assertJson(['data' => ['count' => 0]]);
    }

    public function test_notification_list_excludes_out_of_scope_types(): void
    {
        $user = User::factory()->create();

        // In scope
        $user->notify(new UserActivityNotification('order', 'Narudžba', 'Tekst'));
        // Out of scope for this feed (chat-embedded types are never written
        // here in practice, but the query-level allow-list is what actually
        // enforces the boundary — prove it holds even if something did write one)
        $user->notify(new UserActivityNotification('message', 'Poruka', 'Tekst'));

        $index = $this->actingAs($user)->getJson('/api/v1/notifications');

        $this->assertCount(1, $index->json('data'));
        $this->assertSame('order', $index->json('data.0.type'));
    }

    public function test_get_and_patch_notification_preferences(): void
    {
        // ->fresh() — the factory only sets the columns it lists explicitly,
        // so the in-memory instance never picked up the DB-applied defaults
        // for the rest. Real requests don't hit this (Sanctum re-queries the
        // user from DB on every request); actingAs() reuses this exact
        // instance, so the test needs the refetch that production gets for free.
        $user = User::factory()->create()->fresh();

        $this->actingAs($user)->getJson('/api/v1/users/me/notifications')
            ->assertOk()
            ->assertJson(['data' => [
                'notificationsEnabled' => true,
                'notifyMessages'       => true,
                'notifyOrders'         => true,
                'notifyActivity'       => true,
                'notifyPriceDrops'     => true,
                'notifyAnnouncements'  => true,
            ]]);

        $this->actingAs($user)->patchJson('/api/v1/users/me/notifications', [
            'notify_price_drops' => false,
        ])->assertOk()->assertJson(['data' => ['notifyPriceDrops' => false]]);

        $this->assertFalse($user->fresh()->notify_price_drops);
        $this->assertTrue($user->fresh()->notify_orders);
    }
}
