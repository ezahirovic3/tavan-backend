<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Drop-in replacement for Sanctum's PersonalAccessToken that caches
 * token lookups in Redis for 5 minutes, cutting one DB query per request.
 *
 * Cache is busted automatically when a token is deleted via a per-model
 * ->delete() call. Bulk deletes (e.g. $user->tokens()->delete()) bypass
 * Eloquent model events entirely and will NOT hit that listener — always
 * revoke tokens via User::revokeTokens(), which busts each cache entry
 * itself before deleting.
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

        // Cache raw attributes (plain array) to avoid Eloquent model serialization issues.
        //
        // Cache::remember() can't tell "never cached" apart from "cached,
        // and it's legitimately null" — it treats is_null($value) as a miss
        // either way (Illuminate\Cache\Repository::remember()). A deleted or
        // revoked token has no row, so static::find() returns null forever,
        // which meant the "no such token" result never actually stuck: every
        // request from a client still holding a dead token paid for a fresh
        // DB query, repeated per request indefinitely (PHP-LARAVEL-14, traced
        // back to a bulk token delete never busting this cache — see
        // User::revokeTokens()). Cache `false` for "not found" instead so
        // the negative result sticks.
        $attributes = Cache::remember(
            "sanctum_token_{$id}",
            now()->addMinutes(5),
            fn () => static::find($id)?->getAttributes() ?? false,
        );

        if (! $attributes) {
            return null;
        }

        // Reconstruct a model instance from the cached attributes.
        $instance = new static();
        $instance->setRawAttributes($attributes);
        $instance->exists = true;

        return hash_equals($instance->token, hash('sha256', $plainText))
            ? $instance
            : null;
    }
}
