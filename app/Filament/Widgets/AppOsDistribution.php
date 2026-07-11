<?php

namespace App\Filament\Widgets;

use App\Services\PostHogService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class AppOsDistribution extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Uređaji';

    protected int|string|array $columnSpan = 4;

    protected ?string $pollingInterval = null;

    public function getDescription(): ?string
    {
        $range = PostHogService::resolveRange($this->pageFilters);

        return "Android vs iOS · jedinstveni uređaji, {$range['days']} dana";
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $posthog = app(PostHogService::class);
        $range   = PostHogService::resolveRange($this->pageFilters);
        $scope   = PostHogService::scopeSql($range['from'], $range['to']);

        $rows = $posthog->query(<<<HOGQL
            SELECT
                coalesce(properties.\$os, 'Nepoznato') AS os,
                count(DISTINCT coalesce(properties.\$device_id, distinct_id)) AS devices
            FROM events
            WHERE {$scope}
            GROUP BY os
            ORDER BY devices DESC
            HOGQL) ?? [];

        $labels = array_map(fn ($r) => (string) $r[0], $rows);
        $data   = array_map(fn ($r) => (int) $r[1], $rows);

        return [
            'datasets' => [
                [
                    'data' => $data,
                    // Pink for the lead platform, greys (readable in both
                    // light and dark mode) for the rest.
                    'backgroundColor' => ['#FB5C90', '#8A8A8A', '#C4C4C4', '#E0E0E0'],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '70%',
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => ['boxWidth' => 8, 'usePointStyle' => true],
                ],
            ],
        ];
    }
}
