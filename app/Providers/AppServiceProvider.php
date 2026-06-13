<?php

namespace App\Providers;

use App\Contracts\AuthProviderInterface;
use App\Contracts\OtpProviderInterface;
use App\Models\BrandSuggestion;
use App\Models\CachedPersonalAccessToken;
use App\Observers\BrandSuggestionObserver;
use App\Services\Auth\LocalAuthProvider;
use App\Services\Auth\LogOtpProvider;
use App\Services\Auth\TwilioOtpProvider;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthProviderInterface::class, LocalAuthProvider::class);

        $this->app->bind(OtpProviderInterface::class, function () {
            $sid = config('services.twilio.account_sid');

            if (! $sid) {
                return new LogOtpProvider();
            }

            return new TwilioOtpProvider(
                $sid,
                config('services.twilio.auth_token'),
                config('services.twilio.verify_sid'),
            );
        });
    }

    public function boot(): void
    {
        BrandSuggestion::observe(BrandSuggestionObserver::class);

        // Cache Sanctum token lookups in Redis (5 min TTL) to avoid a DB hit per request.
        Sanctum::usePersonalAccessTokenModel(CachedPersonalAccessToken::class);

        // Allow mobile clients to authenticate private Reverb channels using Sanctum tokens.
        // Without this, the default /broadcasting/auth route uses the web (cookie/session) guard
        // which breaks token-based clients.
        Broadcast::routes(['middleware' => ['auth:sanctum']]);
    }
}
