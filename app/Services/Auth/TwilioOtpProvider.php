<?php

namespace App\Services\Auth;

use App\Contracts\OtpProviderInterface;
use Illuminate\Validation\ValidationException;
use Twilio\Exceptions\RestException;
use Twilio\Rest\Client;

class TwilioOtpProvider implements OtpProviderInterface
{
    private Client $client;

    public function __construct(
        private readonly string $accountSid,
        private readonly string $authToken,
        private readonly string $verifySid,
    ) {
        $this->client = new Client($accountSid, $authToken);
    }

    public function send(string $phone): void
    {
        try {
            $this->client->verify->v2->services($this->verifySid)
                ->verifications
                ->create($phone, 'sms');
        } catch (RestException $e) {
            throw ValidationException::withMessages([
                'phone' => 'Unesite ispravan broj telefona u međunarodnom formatu (npr. +38761123456).',
            ]);
        }
    }

    public function check(string $phone, string $code): bool
    {
        $check = $this->client->verify->v2->services($this->verifySid)
            ->verificationChecks
            ->create(['to' => $phone, 'code' => $code]);

        return $check->status === 'approved';
    }
}
