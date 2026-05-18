<?php

namespace App\Providers;

use App\Contracts\AuthProviderInterface;
use App\Contracts\SmsProviderInterface;
use App\Models\CachedPersonalAccessToken;
use App\Services\Auth\LocalAuthProvider;
use App\Services\Auth\LogSmsProvider;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthProviderInterface::class, LocalAuthProvider::class);

        // In production swap LogSmsProvider for TwilioSmsProvider (or similar)
        $this->app->bind(SmsProviderInterface::class, LogSmsProvider::class);
    }

    public function boot(): void
    {
        // Cache Sanctum token lookups in Redis (5 min TTL) to avoid a DB hit per request.
        Sanctum::usePersonalAccessTokenModel(CachedPersonalAccessToken::class);

        // Allow mobile clients to authenticate private Reverb channels using Sanctum tokens.
        // Without this, the default /broadcasting/auth route uses the web (cookie/session) guard
        // which breaks token-based clients.
        Broadcast::routes(['middleware' => ['auth:sanctum']]);
    }
}
