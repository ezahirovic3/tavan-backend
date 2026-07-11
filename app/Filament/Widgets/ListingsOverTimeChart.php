<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ListingsOverTimeChart extends ChartWidget
{
    protected ?string $heading = 'Oglasi kroz vrijeme';

    protected ?string $description = 'Novi oglasi po sedmici · 12 sedmica';

    protected int|string|array $columnSpan = 8;

    protected ?string $pollingInterval = '300s';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $labels = [];
        $active = [];
        $pending = [];

        for ($i = 11; $i >= 0; $i--) {
            $week = now()->subWeeks($i)->startOfWeek();
            $end  = now()->subWeeks($i)->endOfWeek();

            $labels[] = 'KW ' . $week->isoWeek();

            $active[] = Product::whereBetween('created_at', [$week, $end])
                ->where('status', 'active')->count();

            $pending[] = Product::whereBetween('created_at', [$week, $end])
                ->where('status', 'pending_review')->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Aktivni',
                    'data' => $active,
                    'borderColor' => '#FB5C90',
                    'backgroundColor' => 'rgba(251,92,144,0.08)',
                    'fill' => true,
                    'tension' => 0.25,
                    'borderWidth' => 2,
                    'pointRadius' => 0,
                ],
                [
                    'label' => 'Na pregledu',
                    'data' => $pending,
                    // Mid-grey so the dashed line reads on both the white and
                    // ink-dark card surfaces (near-black vanished in dark mode).
                    'borderColor' => '#8A8A8A',
                    'backgroundColor' => 'transparent',
                    'borderDash' => [4, 3],
                    'tension' => 0.25,
                    'borderWidth' => 1.5,
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
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => ['boxWidth' => 8, 'usePointStyle' => true],
                ],
            ],
            'scales' => [
                'x' => [
                    'grid'  => ['display' => false],
                    'ticks' => ['color' => 'rgba(115,115,115,1)'],
                ],
                'y' => [
                    // Mid-grey works on both light (#FAFAFA) and dark (#0A0A0A) surfaces.
                    'grid'  => ['color' => 'rgba(115,115,115,0.18)'],
                    'ticks' => ['color' => 'rgba(115,115,115,1)'],
                ],
            ],
        ];
    }
}
