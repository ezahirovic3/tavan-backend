<?php

namespace App\Jobs;

use App\Contracts\OtpProviderInterface;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;
use Throwable;
use Twilio\Exceptions\RestException;

/**
 * Delivers a single verification SMS via the OTP provider (Twilio Verify).
 *
 * The whole point of routing sends through a job: the `twilio-otp` rate
 * limiter (see AppServiceProvider) caps how many of these run per minute
 * across the entire app, so a spike of simultaneous registrations trickles
 * out to Twilio at a safe pace instead of arriving as a burst that Twilio
 * answers with 429s and number locks.
 */
class SendPhoneOtpJob implements ShouldQueue
{
    use Queueable;

    public int $backoff = 10;

    public function __construct(public readonly string $phone) {}

    /**
     * Keep attempting (through rate-limit releases and transient Twilio
     * errors) for up to 10 minutes, then let the job fail. A permanent
     * Twilio rejection calls fail() directly and skips the rest.
     */
    public function retryUntil(): DateTime
    {
        return now()->addMinutes(10)->toDateTime();
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        // Over the per-minute budget → the job is released back onto the queue
        // (not counted as a failure) and retried shortly.
        return [new RateLimited('twilio-otp')];
    }

    public function handle(OtpProviderInterface $otp): void
    {
        try {
            $otp->send($this->phone);
        } catch (RestException $e) {
            // Twilio rejected the number itself (invalid, unroutable, blocked).
            // Retrying will not help — log and drop it.
            Log::warning('OTP send rejected by Twilio', [
                'phone'  => $this->phone,
                'code'   => $e->getCode(),
                'detail' => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }

    public function failed(?Throwable $e): void
    {
        Log::error('OTP send job failed', [
            'phone' => $this->phone,
            'error' => $e?->getMessage(),
        ]);
    }
}
