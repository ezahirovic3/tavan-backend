<?php

namespace App\Filament\Widgets;

use App\Services\PostHogService;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class AppActiveUsersChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Aktivni korisnici';

    protected int|string|array $columnSpan = 8;

    protected ?string $pollingInterval = null;

    public function getDescription(): ?string
    {
        $range = PostHogService::resolveRange($this->pageFilters);

        return "Jedinstveni korisnici aplikacije po danu · {$range['days']} dana";
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $posthog = app(PostHogService::class);
        $tz      = PostHogService::TIMEZONE;
        $range   = PostHogService::resolveRange($this->pageFilters);
        $scope   = PostHogService::scopeSql($range['from'], $range['to']);

        $rows = $posthog->query(<<<HOGQL
            SELECT
                toDate(toTimeZone(timestamp, '{$tz}')) AS day,
                count(DISTINCT person_id) AS users
            FROM events
            WHERE {$scope}
            GROUP BY day
            ORDER BY day
            HOGQL) ?? [];

        // Fill missing days with 0 so gaps don't get interpolated away.
        $byDay = [];
        foreach ($rows as $row) {
            $byDay[substr((string) $row[0], 0, 10)] = (int) $row[1];
        }

        $labels = [];
        $data   = [];

        $day = CarbonImmutable::parse($range['from'], 'UTC')->setTimezone($tz)->startOfDay();
        $end = CarbonImmutable::parse($range['to'], 'UTC')->setTimezone($tz);

        while ($day <= $end) {
            $labels[] = $day->format('d.m.');
            $data[]   = $byDay[$day->format('Y-m-d')] ?? 0;
            $day      = $day->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Aktivni korisnici',
                    'data' => $data,
                    'borderColor' => '#FB5C90',
                    'backgroundColor' => 'rgba(251,92,144,0.08)',
                    'fill' => true,
                    'tension' => 0.25,
                    'borderWidth' => 2,
                    'pointRadius' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => [
                    'grid'  => ['display' => false],
                    'ticks' => ['color' => 'rgba(115,115,115,1)', 'maxTicksLimit' => 10],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid'  => ['color' => 'rgba(115,115,115,0.18)'],
                    'ticks' => ['color' => 'rgba(115,115,115,1)', 'precision' => 0],
                ],
            ],
        ];
    }
}
