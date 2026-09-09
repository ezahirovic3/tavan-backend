<?php

namespace Tests\Feature\Auth;

use App\Contracts\OtpProviderInterface;
use App\Jobs\SendPhoneOtpJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhoneOtpTest extends TestCase
{
    use RefreshDatabase;

    // ─── Number parsing: BiH (+387) only ─────────────────────────────────────

    #[DataProvider('foreignNumbers')]
    public function test_send_otp_rejects_non_bosnian_numbers(string $phone): void
    {
        Queue::fake();

        $this->postJson('/api/v1/auth/phone/send-otp', ['phone' => $phone])
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');

        Queue::assertNothingPushed();
    }

    public static function foreignNumbers(): array
    {
        return [
            'italian mobile' => ['+393331234567'],
            'italian with 00' => ['00393331234567'],
            'us number' => ['+14155552671'],
            'bih landline (no SMS)' => ['+38733123456'],
            'too short' => ['+38761123'],
            'garbage' => ['not-a-phone'],
        ];
    }

    #[DataProvider('bosnianNumbers')]
    public function test_send_otp_accepts_and_normalizes_bosnian_numbers(string $input): void
    {
        Queue::fake();

        $this->postJson('/api/v1/auth/phone/send-otp', ['phone' => $input])
            ->assertOk();

        Queue::assertPushed(SendPhoneOtpJob::class, fn (SendPhoneOtpJob $job) => $job->phone === '+38761123456');
    }

    public static function bosnianNumbers(): array
    {
        return [
            'e164' => ['+38761123456'],
            'local 0-prefix' => ['061123456'],
            'local spaced' => ['061 123 456'],
            '00 prefix' => ['0038761123456'],
            'bare digits' => ['38761123456'],
            'doubled country code' => ['+38738761123456'],
        ];
    }

    // ─── Buffering: never stampede Twilio ────────────────────────────────────

    public function test_retry_within_cooldown_is_blocked_without_queueing_a_second_send(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/auth/phone/send-otp', ['phone' => '+38761123456'])->assertOk();

        $this->postJson('/api/v1/auth/phone/send-otp', ['phone' => '+38761123456'])
            ->assertStatus(429)
            ->assertJsonStructure(['message']);

        Queue::assertPushed(SendPhoneOtpJob::class, 1);
    }

    public function test_per_phone_daily_cap_stops_sends_once_reached(): void
    {
        Queue::fake();
        config(['otp.max_per_phone_per_day' => 3]);

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/phone/send-otp', ['phone' => '+38761123456'])->assertOk();
            $this->travel(config('otp.cooldown_seconds') + 1)->seconds();
        }

        $this->postJson('/api/v1/auth/phone/send-otp', ['phone' => '+38761123456'])
            ->assertStatus(429);

        Queue::assertPushed(SendPhoneOtpJob::class, 3);
    }

    public function test_queued_job_delivers_through_the_otp_provider(): void
    {
        $provider = $this->mock(OtpProviderInterface::class);
        $provider->shouldReceive('send')->once()->with('+38761123456');

        (new SendPhoneOtpJob('+38761123456'))->handle($provider);
    }

    // ─── Verify: attempt cap + graceful Twilio failure ──────────────────────

    public function test_wrong_code_is_rejected_then_capped_after_repeated_attempts(): void
    {
        $provider = $this->mock(OtpProviderInterface::class);
        $provider->shouldReceive('check')->andReturn(false);

        config(['otp.max_check_attempts' => 3]);

        Sanctum::actingAs(User::factory()->create());

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/phone/verify-otp', [
                'phone' => '+38761123456',
                'otp'   => '000000',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/phone/verify-otp', [
            'phone' => '+38761123456',
            'otp'   => '000000',
        ])->assertStatus(429);
    }
}
