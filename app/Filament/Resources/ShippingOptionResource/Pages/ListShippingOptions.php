<?php

namespace App\Filament\Resources\ShippingOptionResource\Pages;

use App\Filament\Resources\ShippingOptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShippingOptions extends ListRecords
{
    protected static string $resource = ShippingOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
