<?php

namespace App\Filament\Widgets;

use App\Services\PostHogService;
use Carbon\CarbonImmutable;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

/**
 * "Version Adoption" card from the old Vexo dashboard — users per app
 * version in the selected period, with the version's all-time first-seen
 * date ("Od ...").
 */
class AppVersionAdoption extends Widget
{
    use InteractsWithPageFilters;

    protected string $view = 'filament.widgets.app-version-adoption';

    protected int|string|array $columnSpan = 4;

    protected ?string $pollingInterval = null;

    public function getViewData(): array
    {
        $posthog = app(PostHogService::class);
        $lib     = PostHogService::MOBILE_LIB;
        $range   = PostHogService::resolveRange($this->pageFilters);

        // Users counted inside the selected range; first_seen over all time
        // so "Od <datum>" is the version's real release date, not the range start.
        $rows = $posthog->query(<<<HOGQL
            SELECT
                coalesce(properties.\$app_version, 'Nepoznato') AS version,
                uniqIf(person_id, timestamp >= toDateTime('{$range['from']}') AND timestamp < toDateTime('{$range['to']}')) AS users,
                min(timestamp) AS first_seen
            FROM events
            WHERE properties.\$lib = '{$lib}'
            GROUP BY version
            HAVING users > 0
            ORDER BY users DESC
            LIMIT 6
            HOGQL) ?? [];

        $total = array_sum(array_map(fn ($r) => (int) $r[1], $rows));

        return [
            'rows' => array_map(fn ($r) => [
                'version' => 'v' . ltrim((string) $r[0], 'v'),
                'users'   => (int) $r[1],
                'pct'     => $total > 0 ? round(((int) $r[1] / $total) * 100, 1) : 0,
                'since'   => CarbonImmutable::parse((string) $r[2])
                    ->setTimezone(PostHogService::TIMEZONE)
                    ->format('d.m.Y.'),
            ], $rows),
            'rangeDays' => $range['days'],
        ];
    }
}
