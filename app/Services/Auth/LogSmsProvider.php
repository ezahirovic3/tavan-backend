<?php

namespace App\Services\Auth;

use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Log;

class LogSmsProvider implements SmsProviderInterface
{
    public function send(string $phone, string $message): void
    {
        Log::channel('daily')->info("SMS [{$phone}]: {$message}");
    }
}
