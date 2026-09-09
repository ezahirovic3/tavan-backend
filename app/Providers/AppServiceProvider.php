<?php

namespace App\Providers;

use App\Contracts\AuthProviderInterface;
use App\Contracts\OtpProviderInterface;
use App\Models\BrandSuggestion;
use App\Models\CachedPersonalAccessToken;
use App\Models\Product;
use App\Observers\BrandSuggestionObserver;
use App\Observers\ProductObserver;
use App\Services\Auth\LocalAuthProvider;
use App\Services\Auth\LogOtpProvider;
use App\Services\Auth\TwilioOtpProvider;
use Filament\Tables\Table;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\RateLimiter;
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
        Product::observe(ProductObserver::class);

        // Cache Sanctum token lookups in Redis (5 min TTL) to avoid a DB hit per request.
        Sanctum::usePersonalAccessTokenModel(CachedPersonalAccessToken::class);

        // Allow mobile clients to authenticate private Reverb channels using Sanctum tokens.
        // Without this, the default /broadcasting/auth route uses the web (cookie/session) guard
        // which breaks token-based clients.
        Broadcast::routes(['middleware' => ['auth:sanctum']]);

        // Global drip to Twilio Verify: the queued SendPhoneOtpJob runs at most
        // this many times per minute across the whole app, so a burst of
        // registrations trickles out instead of stampeding the API.
        RateLimiter::for('twilio-otp', fn () => Limit::perMinute(
            max(1, (int) config('otp.twilio_sends_per_minute', 60))
        ));

        // Per-IP burst guard on the OTP endpoints (daily caps live in
        // PhoneVerificationService). Keyed by IP for send, by user for verify.
        RateLimiter::for('phone-otp-send', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));
        RateLimiter::for('phone-otp-verify', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));

        // Filament v4 defers filters behind an "Apply" button by default, which
        // also drops any ?filters[...] passed in the URL (the deferred set boots
        // empty and overwrites them). Live filters restore both behaviours.
        Table::configureUsing(fn (Table $table) => $table->deferFilters(false));
    }
}
