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
            'all' => Tab::make('Svi'),
            'admins' => Tab::make('Adminovi')
                ->modifyQueryUsing(fn ($query) => $query->whereIn('role', ['admin', 'super_admin'])),
            'flagged' => Tab::make('Flagged za review')
                ->modifyQueryUsing(fn ($query) => $query->where('listings_require_review', true))
                ->badge(fn () => \App\Models\User::where('listings_require_review', true)->count())
                ->badgeColor('primary'),
            'verified' => Tab::make('Verificirani')
                ->modifyQueryUsing(fn ($query) => $query->where('is_verified', true)),
        ];
    }
}
