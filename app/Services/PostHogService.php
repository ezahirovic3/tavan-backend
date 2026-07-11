<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Read-only client for the PostHog Query API (HogQL).
 *
 * Powers the "Aplikacija" analytics dashboard in Filament with the data the
 * mobile app sends to PostHog (lifecycle events, sessions, devices). Every
 * query is cached in Redis — PostHog's API is rate-limited, so widgets must
 * never hit it on every dashboard refresh.
 */
class PostHogService
{
    /** Only mobile-app events; the landing page shares the same project. */
    public const MOBILE_LIB = 'posthog-react-native';

    /** Display timezone for buckets (project stores UTC). */
    public const TIMEZONE = 'Europe/Sarajevo';

    public function isConfigured(): bool
    {
        return filled(config('services.posthog.api_key'))
            && filled(config('services.posthog.project_id'));
    }

    /**
     * Run a HogQL query and return the result rows, or null on any failure.
     * Results are cached for $ttl seconds (default 10 min).
     *
     * @return array<int, array<int, mixed>>|null
     */
    public function query(string $hogql, int $ttl = 600): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $cacheKey = 'posthog:query:' . md5($hogql);

        $cached = Cache::get($cacheKey);

        if ($cached === 'FAILED') {
            return null;
        }

        if ($cached !== null) {
            return $cached;
        }

        try {
            $response = Http::withToken(config('services.posthog.api_key'))
                ->timeout(15)
                ->post(
                    rtrim(config('services.posthog.host'), '/')
                        . '/api/projects/' . config('services.posthog.project_id') . '/query',
                    ['query' => ['kind' => 'HogQLQuery', 'query' => $hogql]],
                );
        } catch (\Throwable $e) {
            Log::warning('PostHog query error', ['message' => $e->getMessage()]);
            Cache::put($cacheKey, 'FAILED', 60);

            return null;
        }

        if ($response->failed()) {
            Log::warning('PostHog query failed', [
                'status' => $response->status(),
                'body'   => mb_substr($response->body(), 0, 500),
            ]);

            // Cache the failure briefly so a broken key or rate limit
            // doesn't get hammered on every widget render.
            Cache::put($cacheKey, 'FAILED', 60);

            return null;
        }

        $results = $response->json('results') ?? [];

        Cache::put($cacheKey, $results, $ttl);

        return $results;
    }

    /** First column of the first row, or $default. */
    public function scalar(string $hogql, mixed $default = null, int $ttl = 600): mixed
    {
        $results = $this->query($hogql, $ttl);

        return $results[0][0] ?? $default;
    }

    /**
     * Resolve the dashboard's period filter into concrete UTC bounds.
     *
     * "to" is floored to a 10-minute mark so cache keys stay stable between
     * page loads instead of changing every second.
     *
     * @param  array<string, mixed>|null  $filters  the page's filters state
     * @return array{from: string, to: string, prevFrom: string, prevTo: string, days: int}
     */
    public static function resolveRange(?array $filters): array
    {
        $period = $filters['period'] ?? '14';

        $now = CarbonImmutable::now(self::TIMEZONE);
        $to  = $now->subMinutes($now->minute % 10)->startOfMinute();

        if ($period === 'custom' && (! empty($filters['from']) || ! empty($filters['to']))) {
            $from = ! empty($filters['from'])
                ? CarbonImmutable::parse($filters['from'], self::TIMEZONE)->startOfDay()
                : $to->subDays(14);

            if (! empty($filters['to'])) {
                $customTo = CarbonImmutable::parse($filters['to'], self::TIMEZONE)->addDay()->startOfDay();
                $to = $customTo->min($to);
            }

            if ($from >= $to) {
                $from = $to->subDay();
            }
        } else {
            $days = in_array($period, ['7', '14', '30', '90', '180', '365'], true) ? (int) $period : 14;
            $from = $to->subDays($days);
        }

        $seconds = $from->diffInSeconds($to);

        return [
            'from'     => $from->utc()->format('Y-m-d H:i:s'),
            'to'       => $to->utc()->format('Y-m-d H:i:s'),
            'prevFrom' => $from->subSeconds($seconds)->utc()->format('Y-m-d H:i:s'),
            'prevTo'   => $from->utc()->format('Y-m-d H:i:s'),
            'days'     => max(1, (int) round($seconds / 86400)),
        ];
    }

    /** HogQL WHERE fragment limiting events to the mobile app and a UTC range. */
    public static function scopeSql(string $fromUtc, string $toUtc): string
    {
        $lib = self::MOBILE_LIB;

        return "properties.\$lib = '{$lib}'"
            . " AND timestamp >= toDateTime('{$fromUtc}')"
            . " AND timestamp < toDateTime('{$toUtc}')";
    }
}
