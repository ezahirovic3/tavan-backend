<?php

namespace App\Services\Auth;

use App\Contracts\OtpProviderInterface;
use App\Models\User;

class PhoneVerificationService
{
    public function __construct(private readonly OtpProviderInterface $otp) {}

    public function sendOtp(string $phone): void
    {
        $this->otp->send($phone);
    }

    public function verify(string $phone, string $code): bool
    {
        return $this->otp->check($phone, $code);
    }

    public function markVerified(User $user, string $phone): void
    {
        $user->update([
            'phone'             => $phone,
            'phone_verified_at' => now(),
        ]);
    }
}
