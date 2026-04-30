<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-users';
    protected static \UnitEnum|string|null $navigationGroup = 'Korisnici';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')->label('Ime')->required(),
            TextInput::make('username')->label('Korisničko ime')->required()->unique(ignoreRecord: true),
            TextInput::make('email')->label('Email')->email()->unique(ignoreRecord: true),
            TextInput::make('location')->label('Grad')->nullable(),
            Toggle::make('is_verified')->label('Verificiran'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')->label('')->circular()->defaultImageUrl(fn () => null),
                TextColumn::make('name')->label('Ime')->searchable()->sortable(),
                TextColumn::make('username')->label('@')->searchable()->color('gray'),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('rating')->label('Ocjena')->sortable()
                    ->formatStateUsing(fn ($state) => $state > 0 ? "⭐ {$state}" : '—'),
                TextColumn::make('total_reviews')->label('Recenzija')->sortable(),
                IconColumn::make('is_verified')->label('Verificiran')->boolean(),
                TextColumn::make('created_at')->label('Registriran')->date()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_verified')->label('Verificiran'),
                TernaryFilter::make('email_verified_at')
                    ->label('Email verificiran')
                    ->nullable(),
            ])
            ->actions([
                EditAction::make()->label('Uredi'),
                Action::make('toggleVerified')
                    ->label(fn (User $record) => $record->is_verified ? 'Ukloni verifikaciju' : 'Verificiraj')
                    ->icon(fn (User $record) => $record->is_verified ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (User $record) => $record->is_verified ? 'danger' : 'success')
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
