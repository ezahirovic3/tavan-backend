<?php

namespace App\Filament\Resources\ShareViews\Tables;

use App\Models\ShareView;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShareViewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('entity_type')
                    ->label('Tip')
                    ->badge()
                    ->color(fn ($state) => $state === 'product' ? 'info' : 'success')
                    ->formatStateUsing(fn ($state) => $state === 'product' ? 'Artikal' : 'Profil'),

                TextColumn::make('entity_id')
                    ->label('ID')
                    ->color('gray')
                    ->size('sm')
                    ->fontFamily('mono')
                    ->copyable()
                    ->copyMessage('ID kopiran!'),

                TextColumn::make('total')
                    ->label('Ukupno')
                    ->alignEnd()
                    ->weight('semibold'),

                TextColumn::make('platform_breakdown')
                    ->label('iOS / Android / Desktop')
                    ->state(fn ($record) => "{$record->ios_count} / {$record->android_count} / {$record->desktop_count}")
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('opened_count')
                    ->label('App otvorena')
                    ->alignEnd()
                    ->color('success'),

                TextColumn::make('redirect_count')
                    ->label('Store redirect')
                    ->alignEnd()
                    ->color('warning'),

                TextColumn::make('last_seen')
                    ->label('Zadnje')
                    ->dateTime('d.m.Y. H:i')
                    ->sortable()
                    ->color('gray')
                    ->size('sm'),
            ])
            ->recordAction('details')
            ->recordActions([
                Action::make('details')
                    ->label('Detalji')
                    ->icon('heroicon-m-list-bullet')
                    ->modalHeading(fn ($record) => ucfirst($record->entity_type) . ' · ' . $record->entity_id)
                    ->modalContent(fn ($record) => view('filament.share-view-details', [
                        'events' => ShareView::where('entity_id', $record->entity_id)
                            ->where('entity_type', $record->entity_type)
                            ->orderByDesc('created_at')
                            ->get(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Zatvori'),
            ])
            ->filters([
                SelectFilter::make('entity_type')
                    ->label('Tip')
                    ->options([
                        'product' => 'Artikal',
                        'profile' => 'Profil',
                    ]),
            ])
            ->defaultSort('last_seen', 'desc')
            ->paginated([25, 50, 100]);
    }
}
