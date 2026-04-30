<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Models\Brand;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-tag';
    protected static \UnitEnum|string|null $navigationGroup = 'Katalog';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Naziv')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            FileUpload::make('logo_url')
                ->label('Logo')
                ->image()
                ->disk('r2')
                ->directory('brands')
                ->visibility('public')
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
                ImageColumn::make('logo_url')->label('Logo')->circular(),
                TextColumn::make('name')->label('Naziv')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->color('gray'),
                TextColumn::make('sort_order')->label('Redoslijed')->sortable(),
                IconColumn::make('is_active')->label('Aktivno')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Aktivno'),
            ])
            ->actions([EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit'   => Pages\EditBrand::route('/{record}/edit'),
        ];
    }
}
