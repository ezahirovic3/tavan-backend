<?php

namespace App\Exceptions\Refresh;

/**
 * The listing can never be refreshed in its current state — wrong status.
 * Distinct from RefreshTooRecentException, which is only a matter of waiting.
 */
class RefreshNotEligibleException extends RefreshException
{
    public function errorCode(): string
    {
        return 'refresh_not_eligible';
    }

    public function statusCode(): int
    {
        return 422;
    }
}
