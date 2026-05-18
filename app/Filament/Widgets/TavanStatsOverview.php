<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TavanStatsOverview extends BaseWidget
{
    protected int|string|array $columnSpan = 12;

    protected ?string $pollingInterval = '60s';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $activeListings = Product::where('status', 'active')->count();
        $pendingReview  = Product::where('status', 'pending_review')->count();
        $draft          = Product::where('status', 'draft')->count();
        $sold           = Product::where('status', 'sold')->count();

        $totalUsers     = User::count();
        $newUsersWeek   = User::where('created_at', '>=', now()->subWeek())->count();
        $newListingsWk  = Product::where('created_at', '>=', now()->subWeek())->count();

        $totalRevenue   = Order::where('status', 'completed')->sum('total');
        $totalOrders    = Order::count();

        return [
            // The single PINK card — pulls attention to the headline metric.
            Stat::make('Aktivni oglasi', number_format($activeListings, 0, ',', '.'))
                ->description("$pendingReview na čekanju · $draft draft · $sold prodano")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->extraAttributes(['data-tavan-primary' => 'true'])
                ->chart($this->weeklySeries(Product::class)),

            Stat::make('Korisnici', number_format($totalUsers, 0, ',', '.'))
                ->description("+$newUsersWeek ove sedmice")
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('gray')
                ->chart($this->weeklySeries(User::class)),

            Stat::make('Narudžbe', number_format($totalOrders, 0, ',', '.'))
                ->description('Sve statuse uključeno')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('gray'),

            Stat::make('Promet', number_format($totalRevenue, 0, ',', '.') . ' KM')
                ->description("+$newListingsWk novih oglasa · 7d")
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('gray'),
        ];
    }

    /** Returns 12 weekly counts for sparkline. */
    private function weeklySeries(string $model): array
    {
        $series = [];
        for ($i = 11; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end   = now()->subWeeks($i)->endOfWeek();
            $series[] = $model::whereBetween('created_at', [$start, $end])->count();
        }
        return $series;
    }
}
