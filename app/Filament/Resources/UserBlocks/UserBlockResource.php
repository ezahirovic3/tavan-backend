<?php

namespace App\Filament\Resources\UserBlocks;

use App\Filament\Resources\UserBlocks\Pages\ListUserBlocks;
use App\Models\UserBlock;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only log of who blocked whom in the app.
 * No admin actions; super_admin can delete records.
 */
class UserBlockResource extends Resource
{
    protected static ?string $model = UserBlock::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-no-symbol';

    protected static string|\UnitEnum|null $navigationGroup = 'Moderacija';

    protected static ?string $navigationLabel = 'Blokiranja';

    protected static ?string $modelLabel = 'blokada';

    protected static ?string $pluralModelLabel = 'blokade';

    protected static ?int $navigationSort = 52;

    public static function canCreate(): bool { return false; }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('blocker.username')
                    ->label('Blokirao')
                    ->prefix('@')
                    ->searchable()
                    ->extraAttributes(['class' => 'font-mono text-xs']),

                TextColumn::make('blocked.username')
                    ->label('Blokiran')
                    ->prefix('@')
                    ->searchable()
                    ->extraAttributes(['class' => 'font-mono text-xs']),

                TextColumn::make('reason')
                    ->label('Razlog')
                    ->placeholder('—')
                    ->color('gray')
                    ->limit(48)
                    ->size('sm'),

                TextColumn::make('created_at')
                    ->label('Datum')
                    ->dateTime('d.m.Y. H:i')
                    ->color('gray')
                    ->size('sm')
                    ->sortable(),
            ])
            ->recordActions([
                DeleteAction::make()->visible(fn () => auth()->user()->isSuperAdmin()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => auth()->user()->isSuperAdmin()),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserBlocks::route('/'),
        ];
    }
}
