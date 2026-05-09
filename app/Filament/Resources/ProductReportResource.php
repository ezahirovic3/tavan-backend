<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductReportResource\Pages;
use App\Models\ProductReport;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductReportResource extends Resource
{
    protected static ?string $model = ProductReport::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static \UnitEnum|string|null $navigationGroup = 'Podrška';
    protected static ?string $label = 'Prijava oglasa';
    protected static ?string $pluralLabel = 'Prijave oglasa';
    protected static ?int $navigationSort = 3;

    // ── Permissions ───────────────────────────────────────────────────────────
    // Any admin can view and resolve reports; only super_admin can delete them
    public static function canCreate(): bool              { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return auth()->user()?->isSuperAdmin() ?? false; }
    public static function canDeleteAny(): bool           { return auth()->user()?->isSuperAdmin() ?? false; }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Prijava oglasa')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('reporter.name')->label('Prijavio'),
                    TextEntry::make('product.title')->label('Oglas'),
                ]),
                Grid::make(2)->schema([
                    TextEntry::make('reason')->label('Razlog')->badge()
                        ->color(fn (string $state) => match ($state) {
                            'prohibited' => 'danger',
                            'counterfeit' => 'warning',
                            default      => 'gray',
                        })
                        ->formatStateUsing(fn (string $state) => match ($state) {
                            'counterfeit' => 'Krivotvorina',
                            'prohibited'  => 'Zabranjeni sadržaj',
                            'misleading'  => 'Pogrešan opis',
                            'spam'        => 'Spam/duplikat',
                            'other'       => 'Ostalo',
                            default       => $state,
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
                TextColumn::make('product.title')->label('Oglas')->searchable()->limit(40),
                TextColumn::make('reason')->label('Razlog')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'prohibited'  => 'danger',
                        'counterfeit' => 'warning',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'counterfeit' => 'Krivotvorina',
                        'prohibited'  => 'Zabranjeni sadržaj',
                        'misleading'  => 'Pogrešan opis',
                        'spam'        => 'Spam/duplikat',
                        'other'       => 'Ostalo',
                        default       => $state,
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
                        'counterfeit' => 'Krivotvorina',
                        'prohibited'  => 'Zabranjeni sadržaj',
                        'misleading'  => 'Pogrešan opis',
                        'spam'        => 'Spam/duplikat',
                        'other'       => 'Ostalo',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('review')
                    ->label('Označi pregledanim')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (ProductReport $record) => $record->status === 'pending')
                    ->action(fn (ProductReport $record) => $record->update(['status' => 'reviewed']))
                    ->requiresConfirmation(),
                Action::make('dismiss')
                    ->label('Odbaci')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->visible(fn (ProductReport $record) => $record->status === 'pending')
                    ->action(fn (ProductReport $record) => $record->update(['status' => 'dismissed']))
                    ->requiresConfirmation(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductReports::route('/'),
            'view'  => Pages\ViewProductReport::route('/{record}'),
        ];
    }
}
