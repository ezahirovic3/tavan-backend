<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Svi'),
            'pending_review' => \Filament\Schemas\Components\Tabs\Tab::make('Na pregledu')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'pending_review'))
                ->badge(fn () => \App\Models\Product::where('status', 'pending_review')->count())
                ->badgeColor('primary'),
            'active' => \Filament\Schemas\Components\Tabs\Tab::make('Aktivni')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'active')),
            'draft' => \Filament\Schemas\Components\Tabs\Tab::make('Draft')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'draft')),
            'sold' => \Filament\Schemas\Components\Tabs\Tab::make('Prodano')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'sold')),
        ];
    }
}
