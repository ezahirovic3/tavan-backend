<?php

namespace App\Observers;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

class ProductObserver
{
    private const MILESTONE_LOG_NAME = 'milestone';

    public function created(Product $product): void
    {
        if ($product->status === 'active') {
            $this->checkActiveMilestone($product);
        }
    }

    public function updated(Product $product): void
    {
        if ($product->status === 'active' && $product->wasChanged('status')) {
            $this->checkActiveMilestone($product);
        }
    }

    private function checkActiveMilestone(Product $product): void
    {
        $milestone   = (int) config('tavan.active_product_milestone');
        $cacheKey    = "milestone_{$milestone}_active_flagged";
        $description = "milestone_{$milestone}_active";

        // Fast path once the milestone has been flagged; the activity log
        // below stays the durable source of truth if the cache is flushed.
        if ($milestone < 1 || Cache::get($cacheKey)) {
            return;
        }

        $activeCount = Product::where('status', 'active')->count();

        if ($activeCount < $milestone) {
            return;
        }

        if (Activity::where('log_name', self::MILESTONE_LOG_NAME)
            ->where('description', $description)
            ->exists()) {
            Cache::forever($cacheKey, true);

            return;
        }

        activity(self::MILESTONE_LOG_NAME)
            ->performedOn($product)
            ->withProperties([
                'active_count' => $activeCount,
                'seller_id'    => $product->seller_id,
                'title'        => $product->title,
            ])
            ->log($description);

        Cache::forever($cacheKey, true);

        Log::info("Milestone: {$milestone}. aktivni oglas", [
            'product_id' => $product->id,
            'seller_id'  => $product->seller_id,
        ]);

        $admins = User::whereIn('role', ['admin', 'super_admin'])->get();

        if ($admins->isEmpty()) {
            return;
        }

        $formatted = number_format($milestone, 0, ',', '.');

        Notification::make()
            ->title("🎉 {$formatted}. aktivni oglas!")
            ->body('"' . $product->title . '" od @' . $product->seller?->username . " je oglas koji je donio {$formatted} aktivnih oglasa na Tavanu.")
            ->icon('heroicon-o-trophy')
            ->iconColor('success')
            ->actions([
                Action::make('view')
                    ->label('Pogledaj oglas')
                    ->url(ProductResource::getUrl('view', ['record' => $product]))
                    ->markAsRead(),
            ])
            ->sendToDatabase($admins);
    }
}
