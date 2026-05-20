<?php

namespace App\Filament\Resources\Campaigns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Naziv')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn ($record) => str($record->description ?? '')->limit(60)),

                TextColumn::make('channel')
                    ->label('Kanal')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'instagram'  => 'Instagram',
                        'facebook'   => 'Facebook',
                        'tiktok'     => 'TikTok',
                        'flyer'      => 'Flyer',
                        'influencer' => 'Influencer',
                        default      => 'Ostalo',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active'    => 'success',
                        'paused'    => 'warning',
                        'completed' => 'gray',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active'    => 'Aktivna',
                        'paused'    => 'Pauzirana',
                        'completed' => 'Završena',
                        default     => $state,
                    }),

                TextColumn::make('link')
                    ->label('Link')
                    ->state(fn ($record) => 'tavan.store/go/' . $record->id)
                    ->color('gray')
                    ->size('sm')
                    ->copyable()
                    ->copyMessage('Link kopiran!')
                    ->extraAttributes(['class' => 'font-mono text-xs']),

                TextColumn::make('link_clicks')
                    ->label('Klikovi')
                    ->state(fn ($record) => $record->events()->where('type', 'link_click')->count())
                    ->sortable(false)
                    ->alignEnd(),

                TextColumn::make('total_spend')
                    ->label('Potrošeno')
                    ->state(fn ($record) => number_format($record->expenses()->sum('amount'), 2) . ' KM')
                    ->sortable(false)
                    ->alignEnd(),

                TextColumn::make('starts_at')
                    ->label('Počinje')
                    ->date('d.m.Y.')
                    ->color('gray')
                    ->size('sm')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active'    => 'Aktivna',
                        'paused'    => 'Pauzirana',
                        'completed' => 'Završena',
                    ]),

                SelectFilter::make('channel')
                    ->label('Kanal')
                    ->options([
                        'instagram'  => 'Instagram',
                        'facebook'   => 'Facebook',
                        'tiktok'     => 'TikTok',
                        'flyer'      => 'Flyer',
                        'influencer' => 'Influencer',
                        'other'      => 'Ostalo',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => auth()->user()->isSuperAdmin()),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
