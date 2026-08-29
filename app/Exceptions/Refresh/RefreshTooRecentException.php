<?php

namespace App\Exceptions\Refresh;

use Carbon\CarbonInterface;

/**
 * The listing went live (or was last refreshed) too recently.
 *
 * One exception covers both cases because they are one rule: a listing is
 * refreshable once its effective recency — COALESCE(refreshed_at,
 * published_at, created_at) — is older than tavan.refresh_min_age_days.
 */
class RefreshTooRecentException extends RefreshException
{
    public function __construct(public readonly CarbonInterface $nextEligibleAt)
    {
        parent::__construct(
            'Ovaj oglas možeš osvježiti za ' . self::humanize($nextEligibleAt) . '.'
        );
    }

    public function errorCode(): string
    {
        return 'refresh_too_recent';
    }

    public function statusCode(): int
    {
        return 429;
    }

    public function context(): array
    {
        return [
            // ceil + int cast: Carbon 3 returns a float, and Retry-After must be
            // a whole number of seconds that doesn't land before the real window.
            'retry_after'      => (int) ceil(now()->diffInSeconds($this->nextEligibleAt, absolute: true)),
            'next_eligible_at' => $this->nextEligibleAt->toISOString(),
        ];
    }

    /**
     * "12 dana" / "1 dan" / "5 sati" — Bosnian needs three plural forms, and
     * Carbon's localisation doesn't cover bs well enough to rely on here.
     */
    private static function humanize(CarbonInterface $at): string
    {
        $hours = (int) ceil(now()->diffInMinutes($at, absolute: true) / 60);

        if ($hours >= 24) {
            $days = (int) ceil($hours / 24);

            return $days . ' ' . self::plural($days, 'dan', 'dana', 'dana');
        }

        return max($hours, 1) . ' ' . self::plural(max($hours, 1), 'sat', 'sata', 'sati');
    }

    private static function plural(int $n, string $one, string $few, string $many): string
    {
        $mod10  = $n % 10;
        $mod100 = $n % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return $one;
        }

        if (in_array($mod10, [2, 3, 4], true) && ! in_array($mod100, [12, 13, 14], true)) {
            return $few;
        }

        return $many;
    }
}
