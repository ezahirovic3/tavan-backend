<?php

namespace App\Exceptions;

use Carbon\Carbon;
use Exception;

class AccountBannedException extends Exception
{
    public function __construct(public readonly Carbon $bannedUntil) {}
}
