<?php

namespace App\Contracts;

interface SmsProviderInterface
{
    public function send(string $phone, string $message): void;
}
