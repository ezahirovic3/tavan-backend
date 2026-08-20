<?php

namespace Tests\Feature\Notifications;

use App\Models\Follow;
use App\Models\User;
use App\Notifications\UserActivityNotification;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NewFollowerNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_follow_notifies_the_followed_user(): void
    {
        Notification::fake();
        $this->mock(PushNotificationService::class)
            ->shouldReceive('sendToUser')
            ->once()
            ->withArgs(function (string $userId, string $title, string $body, array $data) {
                return $data['type'] === 'new_follower' && isset($data['followerId']);
            });

        $follower = User::factory()->create(['name' => 'Ana Marić']);
        $seller   = User::factory()->create();

        $this->actingAs($follower)->postJson("/api/v1/users/{$seller->id}/follow")->assertOk();

        Notification::assertSentTo(
            $seller,
            UserActivityNotification::class,
            function (UserActivityNotification $notification) use ($follower) {
                $data = $notification->toDatabase($notification);

                return $data['type'] === 'new_follower'
                    && $data['meta']['followerId'] === $follower->id;
            },
        );
    }

    public function test_re_following_an_already_followed_user_does_not_notify_again(): void
    {
        Notification::fake();

        $follower = User::factory()->create();
        $seller   = User::factory()->create();
        Follow::create(['follower_id' => $follower->id, 'followed_id' => $seller->id]);

        $this->actingAs($follower)->postJson("/api/v1/users/{$seller->id}/follow")->assertOk();

        Notification::assertNothingSent();
    }

    public function test_unfollowing_does_not_notify(): void
    {
        Notification::fake();

        $follower = User::factory()->create();
        $seller   = User::factory()->create();
        Follow::create(['follower_id' => $follower->id, 'followed_id' => $seller->id]);

        $this->actingAs($follower)->deleteJson("/api/v1/users/{$seller->id}/follow")->assertOk();

        Notification::assertNothingSent();
    }
}
