<?php

namespace App\Filament\Resources\SearchQueries\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SearchQueriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('query')
                    ->label('Upit')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->copyable()
                    ->copyMessage('Kopirano!'),

                TextColumn::make('occurrences')
                    ->label('Broj pretraga')
                    ->sortable()
                    ->alignEnd()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 50 => 'danger',
                        $state >= 10 => 'warning',
                        default      => 'gray',
                    }),

                TextColumn::make('last_searched_at')
                    ->label('Zadnja pretraga')
                    ->dateTime('d.m.Y. H:i')
                    ->sortable()
                    ->color('gray')
                    ->size('sm'),

                TextColumn::make('created_at')
                    ->label('Prva pretraga')
                    ->dateTime('d.m.Y. H:i')
                    ->sortable()
                    ->color('gray')
                    ->size('sm')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('occurrences', 'desc')
            ->paginated([25, 50, 100]);
    }
}
