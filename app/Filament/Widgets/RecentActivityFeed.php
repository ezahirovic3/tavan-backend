<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Spatie\Activitylog\Models\Activity;

class RecentActivityFeed extends Widget
{
    protected string $view = 'filament.widgets.recent-activity-feed';

    protected int|string|array $columnSpan = 4;

    protected ?string $pollingInterval = '30s';

    public function getViewData(): array
    {
        return [
            'activities' => Activity::with('causer', 'subject')
                ->latest()
                ->limit(12)
                ->get()
                ->map(fn ($a) => [
                    'when'    => $a->created_at,
                    'who'     => $a->causer?->name ?? 'Sistem',
                    'event'   => match ($a->event) {
                        'created' => 'Kreirao',
                        'updated' => 'Ažurirao',
                        'deleted' => 'Obrisao',
                        default   => $a->event,
                    },
                    'color'   => match ($a->event) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default   => 'gray',
                    },
                    'type'    => match (class_basename($a->subject_type ?? '')) {
                        'Product'  => 'oglas',
                        'User'     => 'korisnika',
                        'BlogPost' => 'blog',
                        'Order'    => 'narudžbu',
                        'Brand'    => 'brend',
                        'Offer'    => 'ponudu',
                        default    => strtolower(class_basename($a->subject_type ?? '')),
                    },
                    'subject' => $a->subject?->getAttribute('title')
                                   ?? $a->subject?->getAttribute('name')
                                   ?? $a->subject?->getAttribute('username')
                                   ?? '#' . $a->subject_id,
                ]),
        ];
    }
}
