<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Korisnici';

    protected static ?string $modelLabel = 'korisnik';

    protected static ?string $pluralModelLabel = 'korisnici';

    protected static ?int $navigationSort = 70;

    protected static ?string $recordTitleAttribute = 'username';

    public const ROLE_OPTIONS = [
        // super_admin omitted intentionally — server-side only.
        'user'  => 'User',
        'admin' => 'Admin',
    ];

    public static function roleColor(string $role): string
    {
        return match ($role) {
            'super_admin' => 'danger',
            'admin'       => 'warning',
            default       => 'gray',
        };
    }

    /**
     * Determines whether this record is read-only in the panel UI.
     * super_admin accounts are server-only.
     */
    public static function isLocked(User $record): bool
    {
        return $record->role === 'super_admin'
            || $record->id === auth()->id(); // self-demotion disabled
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profil')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Ime i prezime')
                            ->required()
                            ->maxLength(120),

                        TextInput::make('username')
                            ->label('Username')
                            ->required()
                            ->maxLength(60)
                            ->prefix('@')
                            ->unique(ignoreRecord: true)
                            ->extraInputAttributes(['class' => 'font-mono']),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),

                        TextInput::make('location')->label('Grad')->maxLength(80),
                    ]),

                Section::make('Role i pravila')
                    ->columns(2)
                    ->schema([
                        Select::make('role')
                            ->label('Role')
                            ->options(self::ROLE_OPTIONS)
                            ->required()
                            ->native(false)
                            ->disabled(fn ($record) => $record && self::isLocked($record))
                            ->helperText('super_admin se postavlja samo putem servera.'),

                        Toggle::make('is_verified')
                            ->label('Verificiran')
                            ->onColor('success')
                            ->disabled(fn ($record) => $record && self::isLocked($record)),

                        Toggle::make('listings_require_review')
                            ->label('Listings zahtijevaju pregled')
                            ->helperText('Svaki novi oglas ovog korisnika ide u pending_review.')
                            ->onColor('danger')
                            ->disabled(fn ($record) => $record && self::isLocked($record)),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->size(36)
                    ->defaultImageUrl(fn ($record) =>
                        'https://ui-avatars.com/api/?name=' . urlencode($record->name ?? '?') .
                        '&background=0A0A0A&color=fff&bold=true'),

                TextColumn::make('name')
                    ->label('Korisnik')
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn ($record) => '@' . $record->username),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->color('gray')
                    ->size('sm')
                    ->copyable(),

                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn ($state) => self::roleColor($state))
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'super_admin' => 'SUPER ADMIN',
                        'admin'       => 'ADMIN',
                        default       => 'USER',
                    })
                    ->extraAttributes(['class' => 'font-mono text-[10px] tracking-widest']),

                IconColumn::make('is_verified')
                    ->label('Verif.')
                    ->boolean()
                    ->trueIcon('heroicon-m-check-badge')
                    ->trueColor('success')
                    ->falseIcon('heroicon-m-minus-small')
                    ->falseColor('gray'),

                IconColumn::make('listings_require_review')
                    ->label('Auto-review')
                    ->boolean()
                    ->trueIcon('heroicon-m-shield-exclamation')
                    ->trueColor('danger')
                    ->falseIcon('heroicon-m-minus-small')
                    ->falseColor('gray'),

                TextColumn::make('profile_view_count')
                    ->label('Pregledi')
                    ->sortable()
                    ->alignEnd()
                    ->color('gray')
                    ->size('sm'),

                TextColumn::make('rating')
                    ->label('Rating')
                    ->numeric(decimalPlaces: 2)
                    ->color('gray')
                    ->size('sm'),

                TextColumn::make('created_at')
                    ->label('Pridružio se')
                    ->date('d.m.Y.')
                    ->color('gray')
                    ->size('sm')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('role')->options([
                    'user' => 'User',
                    'admin' => 'Admin',
                    'super_admin' => 'Super admin',
                ]),
                TernaryFilter::make('is_verified')
                    ->label('Verifikovan')
                    ->placeholder('Svi')
                    ->trueLabel('Samo verifikovani')
                    ->falseLabel('Samo neverifikovani'),
                TernaryFilter::make('listings_require_review')
                    ->label('Auto-review flag')
                    ->placeholder('Svi'),
                Filter::make('hide_deleted')
                    ->label('Sakrij obrisane')
                    ->toggle()
                    ->default(true)
                    ->query(fn (Builder $q, array $data) => $data['isActive']
                        ? $q->whereNull('deleted_at')
                        : $q),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('toggleVerified')
                    ->label(fn ($record) => $record->is_verified ? 'Skini verif.' : 'Verifikuj')
                    ->icon(fn ($record) => $record->is_verified ? 'heroicon-m-x-mark' : 'heroicon-m-check-badge')
                    ->color(fn ($record) => $record->is_verified ? 'gray' : 'success')
                    ->visible(fn ($record) => ! self::isLocked($record))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['is_verified' => ! $record->is_verified]);
                        Notification::make()->success()->title('Status verifikacije ažuriran')->send();
                    }),

                Action::make('toggleAutoReview')
                    ->label(fn ($record) => $record->listings_require_review ? 'Ukloni auto-review' : 'Postavi auto-review')
                    ->icon('heroicon-m-shield-exclamation')
                    ->color(fn ($record) => $record->listings_require_review ? 'gray' : 'danger')
                    ->visible(fn ($record) => ! self::isLocked($record))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['listings_require_review' => ! $record->listings_require_review]);
                        Notification::make()->success()->title('Auto-review flag ažuriran')->send();
                    }),

                EditAction::make()
                    ->visible(fn ($record) => ! self::isLocked($record)),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'view'  => ViewUser::route('/{record}'),
            'edit'  => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'username', 'email'];
    }
}
