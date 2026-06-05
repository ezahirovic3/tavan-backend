<?php

namespace App\Contracts;

interface OtpProviderInterface
{
    public function send(string $phone): void;

    public function check(string $phone, string $code): bool;
}
