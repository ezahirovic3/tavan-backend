<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportInquiryResource\Pages;
use App\Models\SupportInquiry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupportInquiryResource extends Resource
{
    protected static ?string $model = SupportInquiry::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static \UnitEnum|string|null $navigationGroup = 'Podrška';
    protected static ?string $label = 'Upit';
    protected static ?string $pluralLabel = 'Upiti';
    protected static ?int $navigationSort = 1;

    // ── Permissions ───────────────────────────────────────────────────────────
    // Any admin can view and resolve inquiries; only super_admin can delete them
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
            Section::make('Upit')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('user.name')->label('Korisnik (nalog)')->default('—'),
                    TextEntry::make('status')->label('Status')->badge()
                        ->color(fn (string $state) => $state === 'open' ? 'warning' : 'success'),
                ]),
                Grid::make(2)->schema([
                    TextEntry::make('name')->label('Ime')->default('—'),
                    TextEntry::make('email')->label('E-mail')->default('—')
                        ->copyable(),
                ]),
                TextEntry::make('subject')->label('Predmet'),
                TextEntry::make('body')->label('Poruka'),
                TextEntry::make('created_at')->label('Primljeno')->dateTime(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Ime')->default('—')->searchable(),
                TextColumn::make('email')->label('E-mail')->default('—')->searchable(),
                TextColumn::make('user.name')->label('Korisnik (nalog)')->default('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subject')->label('Predmet')->searchable()->limit(60),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (string $state) => $state === 'open' ? 'warning' : 'success'),
                TextColumn::make('created_at')->label('Datum')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(['open' => 'Otvoreno', 'resolved' => 'Riješeno']),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('resolve')
                    ->label('Označi riješenim')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (SupportInquiry $record) => $record->status === 'open')
                    ->action(fn (SupportInquiry $record) => $record->update(['status' => 'resolved']))
                    ->requiresConfirmation(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportInquiries::route('/'),
            'view'  => Pages\ViewSupportInquiry::route('/{record}'),
        ];
    }
}
