<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-folder';
    protected static \UnitEnum|string|null $navigationGroup = 'Katalog';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('parent_id')
                ->label('Nadkategorija')
                ->relationship('parent', 'name')
                ->searchable()
                ->nullable()
                ->placeholder('— Root kategorija —'),

            TextInput::make('name')
                ->label('Naziv')
                ->required()
                ->maxLength(255),

            TextInput::make('key')
                ->label('Ključ')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->helperText('Npr. women-tops-bluze'),

            TextInput::make('icon')
                ->label('Ikona')
                ->maxLength(64)
                ->nullable(),

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
                TextColumn::make('parent.name')->label('Nadkategorija')->placeholder('—')->sortable(),
                TextColumn::make('name')->label('Naziv')->searchable()->sortable(),
                TextColumn::make('key')->label('Ključ')->color('gray'),
                TextColumn::make('sort_order')->label('Redoslijed')->sortable(),
                IconColumn::make('is_active')->label('Aktivno')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Aktivno'),
                SelectFilter::make('parent_id')
                    ->label('Nadkategorija')
                    ->relationship('parent', 'name')
                    ->placeholder('Sve'),
            ])
            ->actions([EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit'   => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
