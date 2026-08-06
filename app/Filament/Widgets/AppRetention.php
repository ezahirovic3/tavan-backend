<?php

namespace App\Filament\Widgets;

use App\Services\PostHogService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

/**
 * "Retention" card from the old Vexo dashboard.
 *
 * Cohort = users whose first-ever event falls inside the selected period.
 * "Eligible" for day N = cohort members who joined at least N days ago;
 * "returned" = eligible members seen again N or more days after joining
 * (unbounded retention).
 */
class AppRetention extends Widget
{
    use InteractsWithPageFilters;

    protected string $view = 'filament.widgets.app-retention';

    protected int|string|array $columnSpan = 4;

    protected ?string $pollingInterval = null;

    private const DAYS = [1, 7, 14, 30];

    /** HogQL for the retention cohorts. Static so the page can pre-warm the cache. */
    public static function query(array $range): string
    {
        $lib = PostHogService::MOBILE_LIB;

        $selects = [];
        foreach (self::DAYS as $n) {
            $selects[] = "countIf(first_seen <= now() - INTERVAL {$n} DAY) AS eligible_{$n}";
            $selects[] = "countIf(first_seen <= now() - INTERVAL {$n} DAY AND last_seen >= first_seen + INTERVAL {$n} DAY) AS returned_{$n}";
        }
        $selectSql = implode(",\n                ", $selects);

        return <<<HOGQL
            SELECT
                {$selectSql}
            FROM (
                SELECT
                    person_id,
                    min(timestamp) AS first_seen,
                    max(timestamp) AS last_seen
                FROM events
                WHERE properties.\$lib = '{$lib}'
                GROUP BY person_id
                HAVING first_seen >= toDateTime('{$range['from']}')
                   AND first_seen < toDateTime('{$range['to']}')
            )
            HOGQL;
    }

    public function getViewData(): array
    {
        $posthog = app(PostHogService::class);
        $range   = PostHogService::resolveRange($this->pageFilters);

        $rows = $posthog->query(self::query($range)) ?? [];

        $row  = $rows[0] ?? [];
        $data = [];

        foreach (self::DAYS as $i => $n) {
            $eligible = (int) ($row[$i * 2] ?? 0);
            $returned = (int) ($row[$i * 2 + 1] ?? 0);

            $data[] = [
                'day'      => $n,
                'eligible' => $eligible,
                'returned' => $returned,
                'pct'      => $eligible > 0 ? round(($returned / $eligible) * 100, 1) : null,
            ];
        }

        return [
            'rows'      => $data,
            'rangeDays' => $range['days'],
        ];
    }
}
