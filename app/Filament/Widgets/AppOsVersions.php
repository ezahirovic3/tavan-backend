<?php

namespace App\Filament\Widgets;

use App\Services\PostHogService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

/** "OS Versions" card from the old Vexo dashboard — users per OS release. */
class AppOsVersions extends Widget
{
    use InteractsWithPageFilters;

    protected string $view = 'filament.widgets.app-os-versions';

    protected int|string|array $columnSpan = 4;

    protected ?string $pollingInterval = null;

    public function getViewData(): array
    {
        $posthog = app(PostHogService::class);
        $range   = PostHogService::resolveRange($this->pageFilters);
        $scope   = PostHogService::scopeSql($range['from'], $range['to']);

        $rows = $posthog->query(<<<HOGQL
            SELECT
                trim(concat(coalesce(properties.\$os, 'Nepoznato'), ' ', coalesce(properties.\$os_version, ''))) AS osv,
                count(DISTINCT person_id) AS users
            FROM events
            WHERE {$scope}
            GROUP BY osv
            ORDER BY users DESC
            LIMIT 6
            HOGQL) ?? [];

        $total = (int) $posthog->scalar("SELECT count(DISTINCT person_id) FROM events WHERE {$scope}", 0);

        return [
            'rows' => array_map(fn ($r) => [
                'label' => (string) $r[0],
                'users' => (int) $r[1],
                'pct'   => $total > 0 ? round(((int) $r[1] / $total) * 100, 1) : 0,
            ], $rows),
            'rangeDays' => $range['days'],
        ];
    }
}
