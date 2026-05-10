<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnnouncementResource\Pages;
use App\Models\Announcement;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\ViewAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-megaphone';
    protected static \UnitEnum|string|null $navigationGroup = 'Sistem';
    protected static ?int $navigationSort = 2;
    protected static ?string $label = 'Obavještenje';
    protected static ?string $pluralLabel = 'Obavještenja';

    // ── Permissions ───────────────────────────────────────────────────────────

    public static function canDelete(Model $record): bool { return true; }
    public static function canDeleteAny(): bool           { return true; }

    // ── Form (create + edit) ──────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Sadržaj')->schema([
                TextInput::make('title')
                    ->label('Naslov')
                    ->required()
                    ->maxLength(255),
                Textarea::make('body')
                    ->label('Poruka')
                    ->required()
                    ->rows(6),
            ]),

            Section::make('Ciljana grupa')->schema([
                Select::make('target_group')
                    ->label('Ko prima ovo obavještenje?')
                    ->options([
                        'all'                      => 'Svi korisnici',
                        'verified'                 => 'Samo verificirani korisnici',
                        'city'                     => 'Korisnici iz određenog grada',
                        'listings_require_review'  => 'Korisnici čiji oglasi zahtijevaju pregled',
                    ])
                    ->default('all')
                    ->required()
                    ->live(),

                TextInput::make('target_value')
                    ->label('Grad')
                    ->placeholder('npr. Sarajevo')
                    ->required()
                    ->visible(fn ($get) => $get('target_group') === 'city'),
            ]),

            Section::make('Vidljivost')->schema([
                DateTimePicker::make('expires_at')
                    ->label('Ističe')
                    ->placeholder('Nikad (ostavite prazno)')
                    ->helperText('Nakon ovog datuma obavještenje neće biti prikazano korisnicima. Ostavite prazno za trajna obavještenja.')
                    ->nullable()
                    ->native(false)
                    ->seconds(false)
                    ->minDate(now()),
            ]),
        ]);
    }

    // ── Infolist (view) ───────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Sadržaj')->schema([
                TextEntry::make('title')->label('Naslov'),
                TextEntry::make('body')->label('Poruka'),
            ]),

            Section::make('Detalji slanja')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('target_group')
                        ->label('Ciljana grupa')
                        ->formatStateUsing(fn (string $state) => match ($state) {
                            'verified'                => 'Verificirani korisnici',
                            'city'                    => 'Grad: ',
                            'listings_require_review' => 'Oglasi zahtijevaju pregled',
                            default                   => 'Svi korisnici',
                        }),
                    TextEntry::make('target_value')->label('Grad')->default('—'),
                    TextEntry::make('creator.name')->label('Poslao'),
                    TextEntry::make('sent_at')->label('Datum slanja')->dateTime('d.m.Y H:i'),
                    TextEntry::make('expires_at')
                        ->label('Ističe')
                        ->dateTime('d.m.Y H:i')
                        ->default('Nikad'),
                    TextEntry::make('reads_count')
                        ->label('Pročitalo')
                        ->state(fn (Announcement $record) => $record->reads()->count() . ' korisnika'),
                ]),
            ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Naslov')
                    ->searchable()
                    ->limit(60),

                TextColumn::make('target_group')
                    ->label('Ciljana grupa')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'verified'                => 'info',
                        'city'                    => 'warning',
                        'listings_require_review' => 'danger',
                        default                   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'verified'                => 'Verificirani',
                        'city'                    => 'Grad',
                        'listings_require_review' => 'Pregled oglasa',
                        default                   => 'Svi',
                    }),

                TextColumn::make('reads_count')
                    ->label('Pročitalo')
                    ->state(fn (Announcement $record) => $record->reads()->count()),

                TextColumn::make('sent_at')
                    ->label('Poslano')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Ističe')
                    ->dateTime('d.m.Y H:i')
                    ->default('—')
                    ->color(fn ($state) => $state && $state < now() ? 'danger' : null)
                    ->sortable(),

                TextColumn::make('creator.name')
                    ->label('Poslao')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('target_group')
                    ->label('Ciljana grupa')
                    ->options([
                        'all'                     => 'Svi',
                        'verified'                => 'Verificirani',
                        'city'                    => 'Grad',
                        'listings_require_review' => 'Pregled oglasa',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('sent_at', 'desc');
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'edit'   => Pages\EditAnnouncement::route('/{record}/edit'),
            'view'   => Pages\ViewAnnouncement::route('/{record}'),
        ];
    }
}
