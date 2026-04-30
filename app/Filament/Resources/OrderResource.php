<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-cart';
    protected static \UnitEnum|string|null $navigationGroup = 'Sadržaj';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Narudžba')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('order_number')->label('Broj'),
                    TextEntry::make('status')->label('Status')->badge()
                        ->color(fn (string $state) => match ($state) {
                            'pending'   => 'warning',
                            'accepted'  => 'info',
                            'shipped'   => 'primary',
                            'delivered' => 'success',
                            'completed' => 'success',
                            'declined'  => 'danger',
                            default     => 'gray',
                        }),
                    TextEntry::make('total')->label('Ukupno')->money('BAM'),
                ]),
                Grid::make(3)->schema([
                    TextEntry::make('subtotal')->label('Iznos')->money('BAM'),
                    TextEntry::make('discount')->label('Popust')->money('BAM'),
                    TextEntry::make('shipping_cost')->label('Dostava')->money('BAM'),
                ]),
                Grid::make(2)->schema([
                    TextEntry::make('payment_method')->label('Plaćanje'),
                    TextEntry::make('delivery_method')->label('Dostava'),
                ]),
            ]),
            Section::make('Kupac / Prodavač')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('buyer.name')->label('Kupac'),
                    TextEntry::make('seller.name')->label('Prodavač'),
                ]),
            ]),
            Section::make('Adresa dostave')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('shipping_name')->label('Ime'),
                    TextEntry::make('shipping_phone')->label('Telefon'),
                    TextEntry::make('shipping_street')->label('Ulica'),
                    TextEntry::make('shipping_city')->label('Grad'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')->label('Broj')->searchable(),
                TextColumn::make('buyer.username')->label('Kupac')->searchable(),
                TextColumn::make('seller.username')->label('Prodavač')->searchable(),
                TextColumn::make('product.title')->label('Proizvod')->limit(30),
                TextColumn::make('total')->label('Ukupno')->money('BAM')->sortable(),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending'   => 'warning',
                        'accepted'  => 'info',
                        'shipped'   => 'primary',
                        'delivered', 'completed' => 'success',
                        'declined'  => 'danger',
                        default     => 'gray',
                    }),
                TextColumn::make('created_at')->label('Datum')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending'   => 'Čekanje',
                        'accepted'  => 'Prihvaćeno',
                        'shipped'   => 'Poslano',
                        'delivered' => 'Dostavljeno',
                        'completed' => 'Završeno',
                        'declined'  => 'Odbijeno',
                    ]),
            ])
            ->actions([ViewAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view'  => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
