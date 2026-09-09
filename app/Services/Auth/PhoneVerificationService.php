<?php

namespace App\Services\Auth;

use App\Contracts\OtpProviderInterface;
use App\Exceptions\OtpThrottleException;
use App\Jobs\SendPhoneOtpJob;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Twilio\Exceptions\RestException;

class PhoneVerificationService
{
    private const DAY = 86400;

    public function __construct(private readonly OtpProviderInterface $otp) {}

    /**
     * Queue a verification SMS for delivery, unless a local guard says no.
     *
     * Guards, checked in order — none of them ever call Twilio:
     *   1. per-phone cooldown  (default 90s between sends to one number)
     *   2. per-phone daily cap (default 5 / 24h)
     *   3. per-IP daily cap    (default 15 / 24h)
     *
     * A blocked send raises OtpThrottleException (→ 429 + retry_after) so the
     * client shows a countdown instead of retrying into Twilio's own lockout.
     */
    public function sendOtp(string $phone, ?string $ip = null): void
    {
        $cooldownKey = $this->cooldownKey($phone);
        $phoneDayKey = 'otp:phone-day:'.sha1($phone);

        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            throw new OtpThrottleException(
                'Kod je već poslan. Provjerite poruke ili pričekajte prije ponovnog slanja.',
                RateLimiter::availableIn($cooldownKey),
            );
        }

        if (RateLimiter::tooManyAttempts($phoneDayKey, $this->maxPerPhonePerDay())) {
            throw new OtpThrottleException(
                'Dostignut je dnevni limit slanja koda za ovaj broj. Pokušajte ponovo sutra.',
                RateLimiter::availableIn($phoneDayKey),
            );
        }

        if ($ip !== null) {
            $ipDayKey = 'otp:ip-day:'.sha1($ip);

            if (RateLimiter::tooManyAttempts($ipDayKey, $this->maxPerIpPerDay())) {
                throw new OtpThrottleException(
                    'Previše zahtjeva za slanje koda sa ovog uređaja. Pokušajte ponovo kasnije.',
                    RateLimiter::availableIn($ipDayKey),
                );
            }

            RateLimiter::hit($ipDayKey, self::DAY);
        }

        RateLimiter::hit($cooldownKey, $this->cooldownSeconds());
        RateLimiter::hit($phoneDayKey, self::DAY);

        SendPhoneOtpJob::dispatch($phone)
            ->onConnection(config('otp.queue_connection'))
            ->onQueue(config('otp.queue'));
    }

    /**
     * Check a code. Caps failed attempts per phone (keeps us under Twilio's
     * own check-attempt lock) and turns Twilio transport errors into a plain
     * "wrong code" instead of a 500.
     */
    public function verify(string $phone, string $code): bool
    {
        $attemptKey = 'otp:check:'.sha1($phone);

        if (RateLimiter::tooManyAttempts($attemptKey, $this->maxCheckAttempts())) {
            throw new OtpThrottleException(
                'Previše pogrešnih pokušaja. Zatražite novi kod.',
                RateLimiter::availableIn($attemptKey),
            );
        }

        try {
            $approved = $this->otp->check($phone, $code);
        } catch (RestException $e) {
            Log::warning('OTP check rejected by Twilio', [
                'phone'  => $phone,
                'detail' => $e->getMessage(),
            ]);

            $approved = false;
        }

        if (! $approved) {
            RateLimiter::hit($attemptKey, $this->checkAttemptWindow());

            return false;
        }

        RateLimiter::clear($attemptKey);
        RateLimiter::clear($this->cooldownKey($phone));

        return true;
    }

    public function markVerified(User $user, string $phone): void
    {
        $user->update([
            'phone'             => $phone,
            'phone_verified_at' => now(),
        ]);
    }

    private function cooldownKey(string $phone): string
    {
        return 'otp:cooldown:'.sha1($phone);
    }

    private function cooldownSeconds(): int
    {
        return max(1, (int) config('otp.cooldown_seconds', 90));
    }

    private function maxPerPhonePerDay(): int
    {
        return max(1, (int) config('otp.max_per_phone_per_day', 5));
    }

    private function maxPerIpPerDay(): int
    {
        return max(1, (int) config('otp.max_per_ip_per_day', 15));
    }

    private function maxCheckAttempts(): int
    {
        return max(1, (int) config('otp.max_check_attempts', 6));
    }

    private function checkAttemptWindow(): int
    {
        return max(60, (int) config('otp.check_attempt_window_seconds', 600));
    }
}
