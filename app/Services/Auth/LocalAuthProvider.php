<?php

namespace App\Services\Auth;

use App\Contracts\AuthProviderInterface;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class LocalAuthProvider implements AuthProviderInterface
{
    public function register(array $data): array
    {
        $user = User::create($data);
        $user->sendEmailVerificationNotification();

        $token = $user->createToken('mobile')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new AuthenticationException();
        }

        // Revoke previous mobile tokens so only one active session exists
        $user->tokens()->where('name', 'mobile')->delete();
        $token = $user->createToken('mobile')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
