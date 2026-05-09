<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-users';
    protected static \UnitEnum|string|null $navigationGroup = 'Korisnici';
    protected static ?int $navigationSort = 1;

    // ── Permissions ───────────────────────────────────────────────────────────

    // Any admin can view and edit users (with field-level guards below)
    // No one can delete users from the panel — accounts are anonymized via the API
    public static function canCreate(): bool    { return false; }
    public static function canDelete(Model $record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }

    // Super admin accounts are read-only from the panel — only editable via server
    public static function canEdit(Model $record): bool
    {
        return ! $record->isSuperAdmin();
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')->label('Ime')->required(),
            TextInput::make('username')->label('Korisničko ime')->required()->unique(ignoreRecord: true),
            TextInput::make('email')->label('Email')->email()->unique(ignoreRecord: true),
            TextInput::make('location')->label('Grad')->nullable(),
            Toggle::make('is_verified')->label('Verificiran'),
            Toggle::make('listings_require_review')
                ->label('Oglasi zahtijevaju pregled')
                ->helperText('Ako je uključeno, novi oglasi ovog prodavača idu na pregled prije objave.'),

            // Role selector — only user/admin visible here.
            // super_admin can only be granted via: php artisan user:set-role
            Select::make('role')
                ->label('Uloga')
                ->options([
                    'user'  => 'Korisnik',
                    'admin' => 'Admin',
                ])
                ->default('user')
                ->required()
                // Prevent any admin from changing their own role
                ->disabled(fn ($record) => $record && auth()->id() === $record->id)
                ->helperText(fn ($record) => ($record && auth()->id() === $record->id)
                    ? 'Ne možeš mijenjati svoju vlastitu ulogu.'
                    : 'super_admin se dodjeljuje isključivo putem servera.'
                ),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')->label('')->circular()->defaultImageUrl(fn () => null),
                TextColumn::make('name')->label('Ime')->searchable()->sortable(),
                TextColumn::make('username')->label('@')->searchable()->color('gray'),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('role')->label('Uloga')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'admin'       => 'warning',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'super_admin' => 'Super Admin',
                        'admin'       => 'Admin',
                        default       => 'Korisnik',
                    }),
                TextColumn::make('rating')->label('Ocjena')->sortable()
                    ->formatStateUsing(fn ($state) => $state > 0 ? "⭐ {$state}" : '—'),
                TextColumn::make('total_reviews')->label('Recenzija')->sortable(),
                IconColumn::make('is_verified')->label('Verificiran')->boolean(),
                IconColumn::make('listings_require_review')->label('Pregled')->boolean()
                    ->trueIcon('heroicon-o-clock')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success'),
                TextColumn::make('created_at')->label('Registriran')->date()->sortable(),
            ])
            ->filters([
                // Hide anonymized accounts by default; toggle to reveal them
                Filter::make('hide_deleted')
                    ->label('Sakrij obrisane')
                    ->query(fn (Builder $query) => $query->where('email', 'not like', '%@deleted.tavan'))
                    ->default(),
                SelectFilter::make('role')
                    ->label('Uloga')
                    ->options([
                        'user'        => 'Korisnik',
                        'admin'       => 'Admin',
                        'super_admin' => 'Super Admin',
                    ]),
                TernaryFilter::make('is_verified')->label('Verificiran'),
                TernaryFilter::make('listings_require_review')->label('Zahtijeva pregled'),
                TernaryFilter::make('email_verified_at')
                    ->label('Email verificiran')
                    ->nullable(),
            ])
            ->actions([
                EditAction::make()->label('Uredi')
                    ->visible(fn (User $record) => ! $record->isSuperAdmin()),
                Action::make('toggleVerified')
                    ->label(fn (User $record) => $record->is_verified ? 'Ukloni verifikaciju' : 'Verificiraj')
                    ->icon(fn (User $record) => $record->is_verified ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (User $record) => $record->is_verified ? 'danger' : 'success')
                    ->visible(fn (User $record) => ! $record->isSuperAdmin())
                    ->action(fn (User $record) => $record->update(['is_verified' => ! $record->is_verified]))
                    ->requiresConfirmation(),
            ])
            ->bulkActions([BulkActionGroup::make([])])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'edit'  => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
