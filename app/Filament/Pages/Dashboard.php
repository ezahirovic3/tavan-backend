<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ListingsOverTimeChart;
use App\Filament\Widgets\QuickModerationQueue;
use App\Filament\Widgets\RecentActivityFeed;
use App\Filament\Widgets\TavanStatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Pregled';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -100;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    public function getColumns(): int|array
    {
        // 4 KPI cards on a 12-grid; chart 8/12 + activity 4/12 below.
        return 12;
    }

    public function getWidgets(): array
    {
        return [
            TavanStatsOverview::class,
            ListingsOverTimeChart::class,
            RecentActivityFeed::class,
            QuickModerationQueue::class,
        ];
    }
}
