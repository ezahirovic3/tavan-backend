<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Svi')
                ->modifyQueryUsing(fn ($query) => $query->where('is_anonymized', false)),
            'admins' => Tab::make('Adminovi')
                ->modifyQueryUsing(fn ($query) => $query
                    ->where('is_anonymized', false)
                    ->whereIn('role', ['admin', 'super_admin'])),
            'flagged' => Tab::make('Flagged za review')
                ->modifyQueryUsing(fn ($query) => $query
                    ->where('is_anonymized', false)
                    ->where('listings_require_review', true))
                ->badge(fn () => \App\Models\User::where('listings_require_review', true)->count())
                ->badgeColor('primary'),
            'verified' => Tab::make('Verificirani')
                ->modifyQueryUsing(fn ($query) => $query
                    ->where('is_anonymized', false)
                    ->where('is_verified', true)),
            'banned' => Tab::make('Banirani')
                ->modifyQueryUsing(fn ($query) => $query
                    ->where('is_anonymized', false)
                    ->where('banned_until', '>', now()))
                ->badge(fn () => \App\Models\User::where('banned_until', '>', now())->count())
                ->badgeColor('danger'),
            'obrisani' => Tab::make('Obrisani')
                ->modifyQueryUsing(fn ($query) => $query->where('is_anonymized', true))
                ->badge(fn () => \App\Models\User::where('is_anonymized', true)->count())
                ->badgeColor('gray'),
        ];
    }
}
