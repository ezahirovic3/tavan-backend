<?php

namespace App\Services\Auth;

use App\Contracts\AuthProviderInterface;
use App\Exceptions\AccountBannedException;
use App\Exceptions\AccountPendingDeletionException;
use App\Exceptions\EmailNotVerifiedException;
use App\Models\User;
use App\Notifications\EmailVerificationOtpNotification;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LocalAuthProvider implements AuthProviderInterface
{
    public function register(array $data): array
    {
        $user = User::create($data);

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('email_verification_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($otp), 'sent_at' => now(), 'created_at' => now()],
        );

        $user->notify(new EmailVerificationOtpNotification($otp));

        return ['status' => 'verification_required', 'email' => $user->email];
    }

    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new AuthenticationException();
        }

        if (! $user->email_verified_at) {
            throw new EmailNotVerifiedException($user->email);
        }

        if ($user->isBanned()) {
            throw new AccountBannedException($user->banned_until);
        }

        if ($user->deletion_requested_at) {
            $recoveryToken = $user->createToken('recovery')->plainTextToken;
            throw new AccountPendingDeletionException(
                $user->deletion_requested_at->addDays(30),
                $recoveryToken,
            );
        }

        $user->tokens()->where('name', 'mobile')->delete();
        $token = $user->createToken('mobile')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
