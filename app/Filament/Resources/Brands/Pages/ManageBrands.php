<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Resources\Brands\BrandResource;
use App\Models\Brand;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBrands extends ManageRecords
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novi brend')
                ->slideOver()
                ->icon('heroicon-m-plus')
                ->using(function (array $data): Brand {
                    Brand::where('sort_order', '>=', $data['sort_order'])->increment('sort_order');

                    return Brand::create($data);
                }),
        ];
    }
}
