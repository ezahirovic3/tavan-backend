<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AppActiveUsersChart;
use App\Filament\Widgets\AppAnalyticsStats;
use App\Filament\Widgets\AppOsDistribution;
use App\Filament\Widgets\AppOsVersions;
use App\Filament\Widgets\AppRetention;
use App\Filament\Widgets\AppUsageHeatmap;
use App\Filament\Widgets\AppVersionAdoption;
use App\Services\PostHogService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use UnitEnum;

/**
 * Mobile-app analytics dashboard fed by PostHog — replaces the old Vexo
 * dashboard (active users, session time, installs, sessions, OS split,
 * usage heatmap, OS/app versions, retention).
 */
class AppAnalytics extends BaseDashboard
{
    use HasFiltersForm {
        updatedFilters as protected updatedFiltersInSession;
    }

    protected static string $routePath = 'aplikacija';

    protected static ?string $title = 'Aplikacija';

    protected static ?string $navigationLabel = 'Aplikacija';

    protected static string|UnitEnum|null $navigationGroup = 'Analitika';

    protected static ?int $navigationSort = -1;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('period')
                ->label('Period')
                ->options([
                    '7'      => 'Zadnjih 7 dana',
                    '14'     => 'Zadnje 2 sedmice',
                    '30'     => 'Zadnji mjesec',
                    '90'     => 'Zadnja 3 mjeseca',
                    '180'    => 'Zadnjih 6 mjeseci',
                    '365'    => 'Zadnja godina',
                    'custom' => 'Prilagođeni period',
                ])
                ->default('14')
                ->selectablePlaceholder(false),

            DatePicker::make('from')
                ->label('Od')
                ->maxDate(now())
                ->visible(fn (Get $get): bool => $get('period') === 'custom'),

            DatePicker::make('to')
                ->label('Do')
                ->maxDate(now())
                ->visible(fn (Get $get): bool => $get('period') === 'custom'),
        ]);
    }

    /** Runs once mount() has resolved $this->filters, before this page's own render(). */
    public function booted(): void
    {
        $this->prefetchWidgetQueries();
    }

    /** Livewire's post-update hook for the `filters` property (see HasFilters::updatedFilters()). */
    public function updatedFilters(): void
    {
        $this->updatedFiltersInSession();
        $this->prefetchWidgetQueries();
    }

    /**
     * Every widget on this dashboard runs its own PostHog query during
     * Livewire hydration. Left alone, that's 6+ widgets each blocking on
     * their own HTTP round trip in sequence (~1s apiece, ~7s total on a
     * cold cache — see Sentry PHP-LARAVEL-11). Pre-warming the shared
     * query cache here, in one Http::pool() batch, means each widget's own
     * PostHogService::query() call below is a cache hit by the time it runs.
     */
    private function prefetchWidgetQueries(): void
    {
        $posthog = app(PostHogService::class);

        if (! $posthog->isConfigured()) {
            return;
        }

        $range = PostHogService::resolveRange($this->filters);

        $posthog->queryMany([
            ...AppAnalyticsStats::queries($range),
            'activeUsersChart' => AppActiveUsersChart::query($range),
            'osDistribution'   => AppOsDistribution::query($range),
            'osVersionsList'   => AppOsVersions::query($range),
            'osVersionsTotal'  => AppOsVersions::totalQuery($range),
            'usageHeatmap'     => AppUsageHeatmap::query($range),
            'versionAdoption'  => AppVersionAdoption::query($range),
            'retention'        => AppRetention::query($range),
        ]);
    }

    public function getSubheading(): ?string
    {
        if (! app(PostHogService::class)->isConfigured()) {
            return 'PostHog nije povezan — dodaj POSTHOG_PROJECT_ID i POSTHOG_PERSONAL_API_KEY (scope: query:read) u .env.';
        }

        return null;
    }

    public function getColumns(): int|array
    {
        return 12;
    }

    public function getWidgets(): array
    {
        return [
            AppAnalyticsStats::class,
            AppActiveUsersChart::class,
            AppOsDistribution::class,
            AppUsageHeatmap::class,
            AppOsVersions::class,
            AppVersionAdoption::class,
            AppRetention::class,
        ];
    }
}
