<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Thrown when an OTP send is refused locally (per-phone cooldown or a daily
 * cap) before it ever reaches Twilio. Renders as 429 with a `retry_after`
 * hint so the mobile app can show a countdown instead of letting the user
 * mash the button and trip Twilio's own lockout.
 */
class OtpThrottleException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $retryAfter = null,
    ) {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        $payload = ['message' => $this->getMessage()];

        if ($this->retryAfter !== null) {
            $payload['retry_after'] = $this->retryAfter;
        }

        $response = new JsonResponse($payload, 429);

        if ($this->retryAfter !== null) {
            $response->headers->set('Retry-After', (string) $this->retryAfter);
        }

        return $response;
    }
}
