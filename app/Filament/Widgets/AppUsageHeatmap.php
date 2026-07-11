<?php

namespace App\Filament\Widgets;

use App\Services\PostHogService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

/**
 * Weekday × hour activity heatmap ("Application Usage" from the old Vexo
 * dashboard). Intensity = number of mobile-app events in that hour slot
 * over the selected period.
 */
class AppUsageHeatmap extends Widget
{
    use InteractsWithPageFilters;

    protected string $view = 'filament.widgets.app-usage-heatmap';

    protected int|string|array $columnSpan = 12;

    protected ?string $pollingInterval = null;

    public function getViewData(): array
    {
        $posthog = app(PostHogService::class);
        $tz      = PostHogService::TIMEZONE;
        $range   = PostHogService::resolveRange($this->pageFilters);
        $scope   = PostHogService::scopeSql($range['from'], $range['to']);

        $rows = $posthog->query(<<<HOGQL
            SELECT
                toDayOfWeek(toTimeZone(timestamp, '{$tz}')) AS dow,
                toHour(toTimeZone(timestamp, '{$tz}')) AS hour,
                count() AS events
            FROM events
            WHERE {$scope}
            GROUP BY dow, hour
            HOGQL) ?? [];

        // grid[dow 1-7 Mon-Sun][hour 0-23] = count
        $grid = array_fill(1, 7, array_fill(0, 24, 0));
        $max  = 0;

        foreach ($rows as $row) {
            $dow  = (int) $row[0];
            $hour = (int) $row[1];
            $c    = (int) $row[2];

            if ($dow >= 1 && $dow <= 7 && $hour >= 0 && $hour <= 23) {
                $grid[$dow][$hour] = $c;
                $max = max($max, $c);
            }
        }

        return [
            'grid'       => $grid,
            'max'        => $max,
            'days'       => [1 => 'Pon', 2 => 'Uto', 3 => 'Sri', 4 => 'Čet', 5 => 'Pet', 6 => 'Sub', 7 => 'Ned'],
            'total'      => array_sum(array_map('array_sum', $grid)),
            'rangeDays'  => $range['days'],
        ];
    }
}
