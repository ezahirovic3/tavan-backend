<?php

namespace Tests\Feature\Follow;

use App\Models\Follow;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowTest extends TestCase
{
    use RefreshDatabase;

    public function test_follow_creates_a_row(): void
    {
        $follower = User::factory()->create();
        $seller   = User::factory()->create();

        $this->actingAs($follower)->postJson("/api/v1/users/{$seller->id}/follow")->assertOk();

        $this->assertDatabaseHas('follows', [
            'follower_id' => $follower->id,
            'followed_id' => $seller->id,
        ]);
    }

    public function test_follow_is_idempotent(): void
    {
        $follower = User::factory()->create();
        $seller   = User::factory()->create();

        $this->actingAs($follower)->postJson("/api/v1/users/{$seller->id}/follow")->assertOk();
        $this->actingAs($follower)->postJson("/api/v1/users/{$seller->id}/follow")->assertOk();

        $this->assertSame(1, Follow::where('follower_id', $follower->id)->where('followed_id', $seller->id)->count());
    }

    public function test_cannot_follow_self(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson("/api/v1/users/{$user->id}/follow")->assertStatus(422);

        $this->assertDatabaseMissing('follows', ['follower_id' => $user->id, 'followed_id' => $user->id]);
    }

    public function test_cannot_follow_a_user_who_blocked_you(): void
    {
        $follower = User::factory()->create();
        $seller   = User::factory()->create();
        UserBlock::create(['blocker_id' => $seller->id, 'blocked_id' => $follower->id]);

        $this->actingAs($follower)->postJson("/api/v1/users/{$seller->id}/follow")->assertStatus(422);
    }

    public function test_cannot_follow_a_user_you_blocked(): void
    {
        $follower = User::factory()->create();
        $seller   = User::factory()->create();
        UserBlock::create(['blocker_id' => $follower->id, 'blocked_id' => $seller->id]);

        $this->actingAs($follower)->postJson("/api/v1/users/{$seller->id}/follow")->assertStatus(422);
    }

    public function test_unfollow_removes_the_row(): void
    {
        $follower = User::factory()->create();
        $seller   = User::factory()->create();
        Follow::create(['follower_id' => $follower->id, 'followed_id' => $seller->id]);

        $this->actingAs($follower)->deleteJson("/api/v1/users/{$seller->id}/follow")->assertOk();

        $this->assertDatabaseMissing('follows', ['follower_id' => $follower->id, 'followed_id' => $seller->id]);
    }

    public function test_unfollow_is_a_safe_no_op_when_not_following(): void
    {
        $follower = User::factory()->create();
        $seller   = User::factory()->create();

        $this->actingAs($follower)->deleteJson("/api/v1/users/{$seller->id}/follow")->assertOk();
    }

    public function test_blocking_severs_an_existing_follow_both_directions(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        // a follows b, and b follows a back
        Follow::create(['follower_id' => $a->id, 'followed_id' => $b->id]);
        Follow::create(['follower_id' => $b->id, 'followed_id' => $a->id]);

        $this->actingAs($a)->postJson("/api/v1/users/{$b->id}/block")->assertOk();

        $this->assertDatabaseMissing('follows', ['follower_id' => $a->id, 'followed_id' => $b->id]);
        $this->assertDatabaseMissing('follows', ['follower_id' => $b->id, 'followed_id' => $a->id]);
    }

    public function test_followers_list_is_public_and_paginated(): void
    {
        $seller = User::factory()->create();
        $a = User::factory()->create();
        $b = User::factory()->create();
        Follow::create(['follower_id' => $a->id, 'followed_id' => $seller->id]);
        Follow::create(['follower_id' => $b->id, 'followed_id' => $seller->id]);

        // No actingAs — guests can view.
        $response = $this->getJson("/api/v1/users/{$seller->id}/followers");

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $this->assertSame(2, $response->json('meta.total'));
    }

    public function test_following_list_returns_who_the_user_follows(): void
    {
        $user = User::factory()->create();
        $sellerA = User::factory()->create();
        $sellerB = User::factory()->create();
        Follow::create(['follower_id' => $user->id, 'followed_id' => $sellerA->id]);
        Follow::create(['follower_id' => $user->id, 'followed_id' => $sellerB->id]);

        $response = $this->getJson("/api/v1/users/{$user->id}/following");

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $this->assertEqualsCanonicalizing(
            [$sellerA->id, $sellerB->id],
            collect($response->json('data'))->pluck('id')->all(),
        );
    }

    public function test_list_rows_expose_is_following_relative_to_the_viewer(): void
    {
        $seller  = User::factory()->create();
        $viewer  = User::factory()->create();
        $alreadyFollowedByViewer = User::factory()->create();
        $notFollowedByViewer     = User::factory()->create();

        Follow::create(['follower_id' => $alreadyFollowedByViewer->id, 'followed_id' => $seller->id]);
        Follow::create(['follower_id' => $notFollowedByViewer->id, 'followed_id' => $seller->id]);
        Follow::create(['follower_id' => $viewer->id, 'followed_id' => $alreadyFollowedByViewer->id]);

        $response = $this->actingAs($viewer)->getJson("/api/v1/users/{$seller->id}/followers");

        $rows = collect($response->json('data'))->keyBy('id');
        $this->assertTrue($rows[$alreadyFollowedByViewer->id]['isFollowing']);
        $this->assertFalse($rows[$notFollowedByViewer->id]['isFollowing']);
    }

    public function test_guest_gets_no_is_following_field(): void
    {
        // Same opt-in pattern as followers_count/item_count elsewhere on
        // UserResource — with no authenticated viewer, is_following is never
        // set, so it's omitted entirely rather than defaulting to false.
        // The mobile client's normalizeUser() already treats a missing field
        // as not-following, so this is intentional, not a gap.
        $seller = User::factory()->create();
        $follower = User::factory()->create();
        Follow::create(['follower_id' => $follower->id, 'followed_id' => $seller->id]);

        $response = $this->getJson("/api/v1/users/{$seller->id}/followers");

        $this->assertArrayNotHasKey('isFollowing', $response->json('data.0'));
    }

    public function test_profile_show_exposes_counts_and_is_following(): void
    {
        $seller = User::factory()->create();
        $viewer = User::factory()->create();
        Follow::create(['follower_id' => $viewer->id, 'followed_id' => $seller->id]);
        Follow::create(['follower_id' => User::factory()->create()->id, 'followed_id' => $seller->id]);

        $response = $this->actingAs($viewer)->getJson("/api/v1/users/{$seller->username}");

        $response->assertOk()->assertJson(['data' => [
            'followersCount' => 2,
            'followingCount' => 0,
            'isFollowing'    => true,
        ]]);
    }

    public function test_auth_me_exposes_own_counts(): void
    {
        $user = User::factory()->create();
        $sellerA = User::factory()->create();
        $sellerB = User::factory()->create();
        Follow::create(['follower_id' => $user->id, 'followed_id' => $sellerA->id]);
        Follow::create(['follower_id' => $user->id, 'followed_id' => $sellerB->id]);

        $response = $this->actingAs($user)->getJson('/api/v1/auth/me');

        $response->assertOk()->assertJson(['data' => [
            'followersCount' => 0,
            'followingCount' => 2,
        ]]);
    }
}
