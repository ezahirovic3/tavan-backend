<?php

namespace App\Filament\Resources\SearchQueries\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\SearchQuery;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            ->paginated([25, 50, 100])
            ->headerActions([
                Action::make('export')
                    ->label('Izvezi CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (): StreamedResponse {
                        $filename = 'pretrage-' . now()->format('Y-m-d') . '.csv';

                        return response()->streamDownload(function () {
                            $handle = fopen('php://output', 'w');
                            fputcsv($handle, ['Upit', 'Broj pretraga', 'Zadnja pretraga', 'Prva pretraga']);

                            SearchQuery::orderByDesc('occurrences')->each(function (SearchQuery $row) use ($handle) {
                                fputcsv($handle, [
                                    $row->query,
                                    $row->occurrences,
                                    $row->last_searched_at?->format('d.m.Y. H:i'),
                                    $row->created_at?->format('d.m.Y. H:i'),
                                ]);
                            });

                            fclose($handle);
                        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
                    }),
            ]);
    }
}
