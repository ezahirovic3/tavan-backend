<?php

namespace App\Services\Auth;

use App\Contracts\OtpProviderInterface;
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

    /**
     * Twilio errors propagate as RestException on purpose: SendPhoneOtpJob
     * inspects them to decide between retrying and giving up.
     */
    public function send(string $phone): void
    {
        $this->client->verify->v2->services($this->verifySid)
            ->verifications
            ->create($phone, 'sms');
    }

    public function check(string $phone, string $code): bool
    {
        $check = $this->client->verify->v2->services($this->verifySid)
            ->verificationChecks
            ->create(['to' => $phone, 'code' => $code]);

        return $check->status === 'approved';
    }
}
