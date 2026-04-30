<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingOptionResource\Pages;
use App\Models\ShippingOption;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShippingOptionResource extends Resource
{
    protected static ?string $model = ShippingOption::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-truck';
    protected static \UnitEnum|string|null $navigationGroup = 'Katalog';
    protected static ?string $label = 'Opcija dostave';
    protected static ?string $pluralLabel = 'Opcije dostave';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('size')
                ->label('Veličina')
                ->options(['S' => 'S', 'M' => 'M', 'L' => 'L'])
                ->required(),

            TextInput::make('label')
                ->label('Naziv')
                ->required()
                ->maxLength(128),

            TextInput::make('price')
                ->label('Cijena (KM)')
                ->numeric()
                ->required()
                ->minValue(0),

            TextInput::make('description')
                ->label('Opis')
                ->nullable()
                ->maxLength(255),

            TextInput::make('sort_order')
                ->label('Redoslijed')
                ->numeric()
                ->default(0),

            Toggle::make('is_active')
                ->label('Aktivno')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('size')->label('Veličina')->badge(),
                TextColumn::make('label')->label('Naziv')->searchable(),
                TextColumn::make('price')->label('Cijena')->money('BAM')->sortable(),
                TextColumn::make('sort_order')->label('Redoslijed')->sortable(),
                IconColumn::make('is_active')->label('Aktivno')->boolean(),
            ])
            ->actions([EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListShippingOptions::route('/'),
            'create' => Pages\CreateShippingOption::route('/create'),
            'edit'   => Pages\EditShippingOption::route('/{record}/edit'),
        ];
    }
}
