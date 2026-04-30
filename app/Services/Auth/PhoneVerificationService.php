<?php

namespace App\Services\Auth;

use App\Contracts\SmsProviderInterface;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class PhoneVerificationService
{
    private const OTP_TTL_MINUTES = 10;
    private const OTP_CACHE_PREFIX = 'phone_otp:';

    public function __construct(private readonly SmsProviderInterface $sms) {}

    public function sendOtp(string $phone): void
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put(
            self::OTP_CACHE_PREFIX.$phone,
            Hash::make($otp),
            now()->addMinutes(self::OTP_TTL_MINUTES)
        );

        $this->sms->send($phone, "Vaš Tavan kod je: {$otp}. Vrijedi {$this->otp_ttl_minutes()} minuta.");
    }

    public function verify(string $phone, string $otp): bool
    {
        $hashed = Cache::get(self::OTP_CACHE_PREFIX.$phone);

        if (! $hashed || ! Hash::check($otp, $hashed)) {
            return false;
        }

        Cache::forget(self::OTP_CACHE_PREFIX.$phone);

        return true;
    }

    public function markVerified(User $user, string $phone): void
    {
        $user->update([
            'phone'              => $phone,
            'phone_verified_at'  => now(),
        ]);
    }

    private function otp_ttl_minutes(): int
    {
        return self::OTP_TTL_MINUTES;
    }
}
