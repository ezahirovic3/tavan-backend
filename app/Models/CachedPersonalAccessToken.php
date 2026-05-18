<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Drop-in replacement for Sanctum's PersonalAccessToken that caches
 * token lookups in Redis for 5 minutes, cutting one DB query per request.
 *
 * Cache is busted automatically when the token is deleted (revoked).
 */
class CachedPersonalAccessToken extends PersonalAccessToken
{
    protected $table = 'personal_access_tokens';

    protected static function booted(): void
    {
        static::deleted(function (self $token) {
            Cache::forget("sanctum_token_{$token->id}");
        });
    }

    /**
     * Override Sanctum's findToken to serve the PAT record from Redis
     * instead of hitting personal_access_tokens on every request.
     */
    public static function findToken($token): static|null
    {
        // Tokens without a pipe are hashed-only (no ID prefix) — fall back to default.
        if (! str_contains($token, '|')) {
            return parent::findToken($token);
        }

        [$id, $plainText] = explode('|', $token, 2);

        /** @var static|null $instance */
        $instance = Cache::remember(
            "sanctum_token_{$id}",
            now()->addMinutes(5),
            fn () => static::find($id),
        );

        if (! $instance) {
            return null;
        }

        return hash_equals($instance->token, hash('sha256', $plainText))
            ? $instance
            : null;
    }
}
