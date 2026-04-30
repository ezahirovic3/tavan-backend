<?php

namespace App\Contracts;

use App\Models\User;

interface AuthProviderInterface
{
    /**
     * Register a new user and return a Sanctum token.
     *
     * @return array{user: User, token: string}
     */
    public function register(array $data): array;

    /**
     * Authenticate an existing user by email + password.
     *
     * @return array{user: User, token: string}
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function login(string $email, string $password): array;

    public function logout(User $user): void;
}
