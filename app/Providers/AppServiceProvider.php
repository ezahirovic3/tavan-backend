<?php

namespace App\Providers;

use App\Contracts\AuthProviderInterface;
use App\Contracts\SmsProviderInterface;
use App\Services\Auth\LocalAuthProvider;
use App\Services\Auth\LogSmsProvider;
use Illuminate\Support\ServiceProvider;

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
        //
    }
}
