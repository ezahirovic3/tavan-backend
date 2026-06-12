<?php

namespace App\Services\Auth;

use App\Contracts\OtpProviderInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LogOtpProvider implements OtpProviderInterface
{
    private const TTL_SECONDS = 600;
    private const PREFIX = 'phone_otp:';

    public function send(string $phone): void
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put(self::PREFIX.$phone, Hash::make($otp), self::TTL_SECONDS);

        Log::channel('daily')->info("SMS [{$phone}]: Vaš Tavan kod je: {$otp}. Vrijedi 10 minuta.");
    }

    public function check(string $phone, string $code): bool
    {
        $hashed = Cache::get(self::PREFIX.$phone);

        if (! $hashed || ! Hash::check($code, $hashed)) {
            return false;
        }

        Cache::forget(self::PREFIX.$phone);

        return true;
    }
}
