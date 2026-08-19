<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ─── Registration ────────────────────────────────────────────────────────

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Test User',
            'username'              => 'testuser',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['status', 'email']]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Another User',
            'username'              => 'anotheruser',
            'email'                 => 'taken@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_register_fails_with_duplicate_username(): void
    {
        User::factory()->create(['username' => 'takenuser']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Another User',
            'username'              => 'takenuser',
            'email'                 => 'new@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('username');
    }

    public function test_register_fails_without_password_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'     => 'Test User',
            'username' => 'testuser',
            'email'    => 'test@example.com',
            'password' => 'password123',
            // password_confirmation intentionally omitted
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_register_rejects_username_with_invalid_characters(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Test User',
            'username'              => 'invalid-user!',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('username');
    }

    // ─── Login ───────────────────────────────────────────────────────────────

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email'    => 'login@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'login@example.com',
            'password' => Hash::make('correctpassword'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'login@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_fails_when_email_is_not_verified(): void
    {
        User::factory()->unverified()->create([
            'email'    => 'unverified@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'unverified@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson(['code' => 'email_not_verified']);
    }

    // ─── Protected routes / me ───────────────────────────────────────────────

    public function test_authenticated_user_can_get_their_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'name', 'email']]);
    }

    public function test_unauthenticated_request_is_rejected_on_protected_routes(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
        $this->postJson('/api/v1/auth/logout')->assertStatus(401);
    }

    // ─── Logout ──────────────────────────────────────────────────────────────

    public function test_user_can_logout(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);
    }

    // ─── Forgot / reset password ────────────────────────────────────────────

    public function test_user_can_reset_password_via_forgot_password_flow(): void
    {
        Notification::fake();

        $user = User::factory()->create(['password' => Hash::make('oldpassword')]);
        $token = $user->createToken('mobile')->plainTextToken;

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
            ->assertStatus(200);

        $otp = null;
        Notification::assertSentTo($user, PasswordResetOtpNotification::class, function ($notification) use (&$otp) {
            $property = new \ReflectionProperty($notification, 'otp');
            $property->setAccessible(true);
            $otp = $property->getValue($notification);

            return true;
        });

        $verifyResponse = $this->postJson('/api/v1/auth/verify-reset-otp', [
            'email' => $user->email,
            'otp'   => $otp,
        ])->assertStatus(200);

        $resetToken = $verifyResponse->json('data.resetToken');
        $this->assertNotEmpty($resetToken);

        $this->postJson('/api/v1/auth/reset-password', [
            'email'                   => $user->email,
            'resetToken'              => $resetToken,
            'newPassword'             => 'newpassword123',
            'newPasswordConfirmation' => 'newpassword123',
        ])->assertStatus(200);

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));

        // Resetting must revoke every existing session, including the one that
        // requested the reset.
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_reset_password_fails_with_missing_reset_token(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/reset-password', [
            'email'                   => $user->email,
            'newPassword'             => 'newpassword123',
            'newPasswordConfirmation' => 'newpassword123',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reset_token']);
    }

    // ─── Change password ─────────────────────────────────────────────────────

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('oldpassword')]);

        $response = $this->actingAs($user)->postJson('/api/v1/auth/change-password', [
            'currentPassword'           => 'oldpassword',
            'newPassword'               => 'newpassword123',
            'newPassword_confirmation'  => 'newpassword123',
        ]);

        $response->assertStatus(200);
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_change_password_revokes_other_sessions_but_keeps_current_one(): void
    {
        $user          = User::factory()->create(['password' => Hash::make('oldpassword')]);
        $currentToken  = $user->createToken('this-device')->plainTextToken;
        $otherToken    = $user->createToken('other-device')->plainTextToken;

        $this->withToken($currentToken)->postJson('/api/v1/auth/change-password', [
            'currentPassword'          => 'oldpassword',
            'newPassword'              => 'newpassword123',
            'newPassword_confirmation' => 'newpassword123',
        ])->assertStatus(200);

        $this->withToken($currentToken)->getJson('/api/v1/auth/me')->assertStatus(200);
        $this->withToken($otherToken)->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_change_password_fails_with_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correctpassword')]);

        $response = $this->actingAs($user)->postJson('/api/v1/auth/change-password', [
            'currentPassword'           => 'wrongpassword',
            'newPassword'               => 'newpassword123',
            'newPassword_confirmation'  => 'newpassword123',
        ]);

        $response->assertStatus(422);
    }
}
