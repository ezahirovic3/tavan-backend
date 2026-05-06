<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserBlockResource\Pages;
use App\Models\UserBlock;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserBlockResource extends Resource
{
    protected static ?string $model = UserBlock::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-no-symbol';
    protected static \UnitEnum|string|null $navigationGroup = 'Podrška';
    protected static ?string $label = 'Blokiranje';
    protected static ?string $pluralLabel = 'Blokiranja';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('blocker.name')->label('Blokirao')->searchable(),
                TextColumn::make('blocker.username')->label('Username')->searchable(),
                TextColumn::make('blocked.name')->label('Blokiran')->searchable(),
                TextColumn::make('blocked.username')->label('Username'),
                TextColumn::make('created_at')->label('Datum')->dateTime()->sortable(),
            ])
            ->actions([
                DeleteAction::make()->label('Ukloni'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserBlocks::route('/'),
        ];
    }
}
