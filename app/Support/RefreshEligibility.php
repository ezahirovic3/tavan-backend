<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Whether a listing can be refreshed right now, and if not, why.
 *
 * Produced by App\Services\ListingRefreshService::eligibilityFor() and consumed
 * by both the endpoint and ProductResource, so the button state the client
 * renders and the answer the server gives can never disagree.
 */
readonly class RefreshEligibility
{
    private function __construct(
        public bool $canRefresh,
        public ?CarbonInterface $nextEligibleAt = null,
        public ?string $blockedReason = null,
    ) {}

    public static function allowed(): self
    {
        return new self(true);
    }

    /** @param 'not_active'|'too_recent' $reason */
    public static function blocked(string $reason, ?CarbonInterface $nextEligibleAt = null): self
    {
        return new self(false, $nextEligibleAt, $reason);
    }
}
