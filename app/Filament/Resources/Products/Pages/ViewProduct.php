<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('changeBrand')
                ->label('Promijeni brend')
                ->icon('heroicon-m-tag')
                ->color('gray')
                ->schema([
                    Select::make('brand_id')
                        ->label('Brend')
                        ->relationship('brand', 'name')
                        ->searchable()
                        ->preload()
                        ->placeholder('— Bez brenda —'),
                ])
                ->fillForm(fn () => ['brand_id' => $this->record->brand_id])
                ->modalHeading('Promijeni brend')
                ->modalSubmitActionLabel('Sačuvaj')
                ->action(function (array $data) {
                    $this->record->update(['brand_id' => $data['brand_id'] ?: null]);
                    Notification::make()->success()->title('Brend ažuriran')->send();
                }),

            EditAction::make(),
        ];
    }
}
