<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Services\ProductReviewService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Odobri oglas')
                ->icon('heroicon-m-check')
                ->color('success')
                ->visible(fn () => $this->record->status === 'pending_review')
                ->requiresConfirmation()
                ->modalHeading('Odobri oglas')
                ->modalDescription(fn () => $this->record->seller?->listings_require_review
                    ? 'Oglas postaje aktivan. Prodavac dobija obavijest da su budući oglasi odobreni i idu direktno online.'
                    : 'Oglas postaje aktivan i vidljiv u aplikaciji.')
                ->action(function () {
                    $sellerApproved = app(ProductReviewService::class)->approve($this->record, auth()->user());

                    Notification::make()
                        ->success()
                        ->title($sellerApproved
                            ? 'Oglas odobren — prodavac odobren, poruka poslana'
                            : 'Oglas odobren')
                        ->send();

                    $this->record->refresh();
                }),

            Action::make('reject')
                ->label('Odbaci oglas')
                ->icon('heroicon-m-x-mark')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'pending_review')
                ->schema([
                    Textarea::make('reason')
                        ->label('Razlog odbijanja')
                        ->required()
                        ->rows(3)
                        ->helperText('Šalje se prodavcu kao poruka u support konverzaciji.'),
                ])
                ->modalHeading('Odbaci oglas')
                ->action(function (array $data) {
                    app(ProductReviewService::class)->reject($this->record, auth()->user(), $data['reason']);

                    Notification::make()->success()->title('Oglas odbačen, poruka poslana prodavcu')->send();

                    $this->record->refresh();
                }),

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
