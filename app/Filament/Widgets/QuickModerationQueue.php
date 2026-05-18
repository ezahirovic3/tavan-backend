<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\ProductReport;
use App\Models\UserReport;
use Filament\Widgets\Widget;

class QuickModerationQueue extends Widget
{
    protected string $view = 'filament.widgets.quick-moderation-queue';

    protected int|string|array $columnSpan = 12;

    public function getViewData(): array
    {
        return [
            'pendingProducts' => Product::where('status', 'pending_review')->count(),
            'openProductReports' => ProductReport::where('status', 'pending')->count(),
            'openUserReports' => UserReport::where('status', 'pending')->count(),
        ];
    }
}
