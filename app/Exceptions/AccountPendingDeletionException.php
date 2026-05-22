<?php

namespace App\Exceptions;

use Carbon\Carbon;
use RuntimeException;

class AccountPendingDeletionException extends RuntimeException
{
    public function __construct(
        public readonly Carbon $deletionDate,
        public readonly string $recoveryToken,
    ) {
        parent::__construct('Account is pending deletion.');
    }
}
