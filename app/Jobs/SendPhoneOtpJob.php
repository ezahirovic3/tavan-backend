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

    /**
     * Real errors (not rate-limit releases) allowed before the job fails.
     * Every attempt is a Twilio send attempt, and Verify locks a number after
     * ~5 sends in 10 minutes — so retry sparingly.
     */
    public int $maxExceptions = 2;

    public int $backoff = 30;

    public function __construct(public readonly string $phone) {}

    /**
     * A code that shows up minutes late is useless — by then the user has
     * either received a later one or given up. Stop well before Verify's
     * 10-minute code expiry.
     */
    public function retryUntil(): DateTime
    {
        return now()->addMinutes(2)->toDateTime();
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
            if ($this->isTransient($e)) {
                // Twilio-side throttling or outage: one more try is fine.
                throw $e;
            }

            // Twilio rejected the number itself (invalid, unroutable, blocked,
            // max send attempts reached). Retrying only extends the lock.
            Log::warning('OTP send rejected by Twilio', [
                'phone'  => $this->phone,
                'status' => $e->getStatusCode(),
                'code'   => $e->getCode(),
                'detail' => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }

    private function isTransient(RestException $e): bool
    {
        // 20429 = Twilio API rate limit (global, not per-number).
        return $e->getCode() === 20429 || $e->getStatusCode() >= 500;
    }

    public function failed(?Throwable $e): void
    {
        Log::error('OTP send job failed', [
            'phone' => $this->phone,
            'error' => $e?->getMessage(),
        ]);
    }
}
