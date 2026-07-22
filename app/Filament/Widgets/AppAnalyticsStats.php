<?php

namespace App\Filament\Widgets;

use App\Services\PostHogService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * KPI row of the "Aplikacija" analytics dashboard — replicates the old Vexo
 * header: Active Users, Session Time, Installs, Total Sessions, each with a
 * delta vs the previous period. Data comes from PostHog (mobile app events).
 */
class AppAnalyticsStats extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 12;

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $posthog = app(PostHogService::class);

        if (! $posthog->isConfigured()) {
            return [
                Stat::make('PostHog', '—')
                    ->description('Dodaj POSTHOG_PERSONAL_API_KEY u .env')
                    ->color('gray'),
            ];
        }

        $range = PostHogService::resolveRange($this->pageFilters);
        $tz    = PostHogService::TIMEZONE;

        $scope     = PostHogService::scopeSql($range['from'], $range['to']);
        $prevScope = PostHogService::scopeSql($range['prevFrom'], $range['prevTo']);

        // All queries for this widget fire concurrently (Http::pool) instead
        // of one round trip each — sequential HTTP to PostHog was blocking
        // this Livewire request for ~6s on a cold cache.
        $results = $posthog->queryMany([
            'daily' => <<<HOGQL
                SELECT
                    toDate(toTimeZone(timestamp, '{$tz}')) AS day,
                    count(DISTINCT person_id) AS users
                FROM events
                WHERE {$scope}
                GROUP BY day
                ORDER BY day
                HOGQL,
            'activeNow'    => "SELECT count(DISTINCT person_id) FROM events WHERE {$scope}",
            'activePrev'   => "SELECT count(DISTINCT person_id) FROM events WHERE {$prevScope}",
            'sessionsNow'  => $this->sessionStatsSql($scope),
            'sessionsPrev' => $this->sessionStatsSql($prevScope),
            'installsNow'  => "SELECT count() FROM events WHERE event = 'Application Installed' AND {$scope}",
            'installsPrev' => "SELECT count() FROM events WHERE event = 'Application Installed' AND {$prevScope}",
        ]);

        $daily      = $results['daily'] ?? [];
        $userSeries = array_map(fn ($row) => (int) $row[1], $daily);

        $activeNow  = (int) ($results['activeNow'][0][0] ?? 0);
        $activePrev = (int) ($results['activePrev'][0][0] ?? 0);

        [$sessionsNow, $avgDurNow]   = $this->sessionStatsResult($results['sessionsNow'] ?? null);
        [$sessionsPrev, $avgDurPrev] = $this->sessionStatsResult($results['sessionsPrev'] ?? null);

        $installsNow  = (int) ($results['installsNow'][0][0] ?? 0);
        $installsPrev = (int) ($results['installsPrev'][0][0] ?? 0);

        $suffix = " · {$range['days']}d";

        return [
            Stat::make('Aktivni korisnici' . $suffix, number_format($activeNow, 0, ',', '.'))
                ->description($this->deltaLabel($activeNow, $activePrev))
                ->descriptionIcon($this->deltaIcon($activeNow, $activePrev))
                ->extraAttributes(['data-tavan-primary' => 'true'])
                ->chart($userSeries ?: [0]),

            Stat::make('Trajanje sesije · prosjek', $this->formatDuration($avgDurNow))
                ->description($this->deltaLabel((int) $avgDurNow, (int) $avgDurPrev))
                ->descriptionIcon($this->deltaIcon((int) $avgDurNow, (int) $avgDurPrev))
                ->color('gray'),

            Stat::make('Instalacije' . $suffix, number_format($installsNow, 0, ',', '.'))
                ->description($this->deltaLabel($installsNow, $installsPrev))
                ->descriptionIcon($this->deltaIcon($installsNow, $installsPrev))
                ->color('gray'),

            Stat::make('Sesije' . $suffix, number_format($sessionsNow, 0, ',', '.'))
                ->description($this->deltaLabel($sessionsNow, $sessionsPrev))
                ->descriptionIcon($this->deltaIcon($sessionsNow, $sessionsPrev))
                ->color('gray')
                ->chart($userSeries ?: [0]),
        ];
    }

    private function sessionStatsSql(string $scope): string
    {
        return <<<HOGQL
            SELECT count() AS sessions, avg(dur) AS avg_dur
            FROM (
                SELECT
                    properties.\$session_id AS sid,
                    dateDiff('second', min(timestamp), max(timestamp)) AS dur
                FROM events
                WHERE {$scope}
                  AND properties.\$session_id IS NOT NULL
                GROUP BY sid
            )
            HOGQL;
    }

    /** @return array{0: int, 1: float} [session count, avg duration seconds] */
    private function sessionStatsResult(?array $rows): array
    {
        return [(int) ($rows[0][0] ?? 0), (float) ($rows[0][1] ?? 0)];
    }

    private function formatDuration(float $seconds): string
    {
        if ($seconds < 1) {
            return '—';
        }

        $m = intdiv((int) $seconds, 60);
        $s = ((int) $seconds) % 60;

        return $m > 0 ? "{$m}m {$s}s" : "{$s}s";
    }

    private function deltaLabel(int|float $now, int|float $prev): string
    {
        if ($prev <= 0) {
            return 'Nema podataka za prethodni period';
        }

        $pct = (int) round((($now - $prev) / $prev) * 100);

        if ($pct === 0) {
            return '±0% u odnosu na prethodni period';
        }

        return ($pct > 0 ? '+' : '') . $pct . '% u odnosu na prethodni period';
    }

    private function deltaIcon(int|float $now, int|float $prev): string
    {
        return $now >= $prev
            ? 'heroicon-m-arrow-trending-up'
            : 'heroicon-m-arrow-trending-down';
    }
}
