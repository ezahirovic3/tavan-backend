<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandSuggestionResource\Pages;
use App\Models\BrandSuggestion;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BrandSuggestionResource extends Resource
{
    protected static ?string $model = BrandSuggestion::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-light-bulb';
    protected static \UnitEnum|string|null $navigationGroup = 'Katalog';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Prijedlozi brendova';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Predloženi brend')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.username')
                    ->label('Korisnik')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending'  => 'Na čekanju',
                        'approved' => 'Odobreno',
                        'rejected' => 'Odbijeno',
                    }),

                TextColumn::make('created_at')
                    ->label('Datum')
                    ->dateTime('d.m.Y. H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending'  => 'Na čekanju',
                        'approved' => 'Odobreno',
                        'rejected' => 'Odbijeno',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Odobri')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (BrandSuggestion $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(fn (BrandSuggestion $record) => $record->update(['status' => 'approved'])),

                Action::make('reject')
                    ->label('Odbij')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (BrandSuggestion $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(fn (BrandSuggestion $record) => $record->update(['status' => 'rejected'])),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBrandSuggestions::route('/'),
        ];
    }
}
