<?php

namespace App\Filament\Resources\ShippingOptionResource\Pages;

use App\Filament\Resources\ShippingOptionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditShippingOption extends EditRecord
{
    protected static string $resource = ShippingOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
