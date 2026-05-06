<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserReportResource\Pages;
use App\Models\UserReport;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UserReportResource extends Resource
{
    protected static ?string $model = UserReport::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-flag';
    protected static \UnitEnum|string|null $navigationGroup = 'Podrška';
    protected static ?string $label = 'Prijava';
    protected static ?string $pluralLabel = 'Prijave korisnika';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Prijava')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('reporter.name')->label('Prijavio'),
                    TextEntry::make('reported.name')->label('Prijavljeni'),
                ]),
                Grid::make(2)->schema([
                    TextEntry::make('reason')->label('Razlog')->badge()
                        ->color(fn (string $state) => match ($state) {
                            'harassment' => 'danger',
                            'spam'       => 'warning',
                            default      => 'gray',
                        })
                        ->formatStateUsing(fn (string $state) => match ($state) {
                            'spam'          => 'Spam',
                            'inappropriate' => 'Neprikladni sadržaj',
                            'harassment'    => 'Uznemiravanje',
                            'fake'          => 'Lažni profil',
                            'other'         => 'Ostalo',
                            default         => $state,
                        }),
                    TextEntry::make('status')->label('Status')->badge()
                        ->color(fn (string $state) => match ($state) {
                            'pending'   => 'warning',
                            'reviewed'  => 'success',
                            'dismissed' => 'gray',
                            default     => 'gray',
                        }),
                ]),
                TextEntry::make('description')->label('Opis')->default('—'),
                TextEntry::make('created_at')->label('Primljeno')->dateTime(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reporter.name')->label('Prijavio')->searchable(),
                TextColumn::make('reported.name')->label('Prijavljeni')->searchable(),
                TextColumn::make('reason')->label('Razlog')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'harassment' => 'danger',
                        'spam'       => 'warning',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'spam'          => 'Spam',
                        'inappropriate' => 'Neprikladni sadržaj',
                        'harassment'    => 'Uznemiravanje',
                        'fake'          => 'Lažni profil',
                        'other'         => 'Ostalo',
                        default         => $state,
                    }),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending'   => 'warning',
                        'reviewed'  => 'success',
                        'dismissed' => 'gray',
                        default     => 'gray',
                    }),
                TextColumn::make('created_at')->label('Datum')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending'   => 'Na čekanju',
                        'reviewed'  => 'Pregledano',
                        'dismissed' => 'Odbačeno',
                    ]),
                SelectFilter::make('reason')
                    ->label('Razlog')
                    ->options([
                        'spam'          => 'Spam',
                        'inappropriate' => 'Neprikladni sadržaj',
                        'harassment'    => 'Uznemiravanje',
                        'fake'          => 'Lažni profil',
                        'other'         => 'Ostalo',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('review')
                    ->label('Označi pregledanim')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (UserReport $record) => $record->status === 'pending')
                    ->action(fn (UserReport $record) => $record->update(['status' => 'reviewed']))
                    ->requiresConfirmation(),
                Action::make('dismiss')
                    ->label('Odbaci')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->visible(fn (UserReport $record) => $record->status === 'pending')
                    ->action(fn (UserReport $record) => $record->update(['status' => 'dismissed']))
                    ->requiresConfirmation(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserReports::route('/'),
            'view'  => Pages\ViewUserReport::route('/{record}'),
        ];
    }
}
