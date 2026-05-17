<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Resources\ActivityLogs\Pages\ViewActivityLog;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

/**
 * Audit trail of all admin actions, powered by spatie/laravel-activitylog.
 * Read-only for all admins. Delete is super_admin only.
 */
class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Log aktivnosti';

    protected static ?string $modelLabel = 'aktivnost';

    protected static ?string $pluralModelLabel = 'log aktivnosti';

    protected static ?int $navigationSort = 71;

    public static function canCreate(): bool { return false; }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; }

    public static function eventColor(string $event): string
    {
        return match ($event) {
            'created' => 'success',
            'updated' => 'warning',
            'deleted' => 'danger',
            default   => 'gray',
        };
    }

    public static function eventLabel(string $event): string
    {
        return match ($event) {
            'created' => 'Kreiran',
            'updated' => 'Ažuriran',
            'deleted' => 'Obrisan',
            default   => ucfirst($event),
        };
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Aktivnost')->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('created_at')->label('Vrijeme')->dateTime('d.m.Y. H:i:s'),
                        TextEntry::make('causer.name')->label('Admin')->placeholder('Sistem'),
                        TextEntry::make('event')
                            ->label('Akcija')
                            ->badge()
                            ->color(fn ($state) => static::eventColor($state ?? ''))
                            ->formatStateUsing(fn ($state) => static::eventLabel($state ?? '')),
                        TextEntry::make('subject_type')
                            ->label('Tip')
                            ->badge()
                            ->color('gray')
                            ->formatStateUsing(fn ($state) => class_basename($state ?? '')),
                    ]),
                    TextEntry::make('description')->label('Opis')->columnSpanFull(),
                ]),

                Section::make('Promjene')
                    ->description('Stare i nove vrijednosti')
                    ->schema([
                        Grid::make(2)->schema([
                            KeyValueEntry::make('properties.old')
                                ->label('Stara vrijednost')
                                ->state(fn ($record) => $record->properties['old'] ?? [])
                                ->columnSpan(1),
                            KeyValueEntry::make('properties.attributes')
                                ->label('Nova vrijednost')
                                ->state(fn ($record) => $record->properties['attributes'] ?? [])
                                ->columnSpan(1),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Vrijeme')
                    ->dateTime('d.m.Y. H:i:s')
                    ->extraAttributes(['class' => 'font-mono text-xs'])
                    ->sortable(),

                TextColumn::make('causer.name')
                    ->label('Admin')
                    ->placeholder('Sistem')
                    ->weight('semibold'),

                TextColumn::make('event')
                    ->label('Akcija')
                    ->badge()
                    ->color(fn ($state) => static::eventColor($state ?? ''))
                    ->formatStateUsing(fn ($state) => static::eventLabel($state ?? '')),

                TextColumn::make('subject_type')
                    ->label('Tip')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => class_basename($state ?? ''))
                    ->extraAttributes(['class' => 'font-mono text-[10px] uppercase tracking-wider']),

                TextColumn::make('subject')
                    ->label('Subjekt')
                    ->state(fn ($record) =>
                        $record->subject?->getAttribute('title')
                        ?? $record->subject?->getAttribute('name')
                        ?? $record->subject?->getAttribute('username')
                        ?? '#' . $record->subject_id)
                    ->limit(48),

                TextColumn::make('changed_summary')
                    ->label('Promijenjena polja')
                    ->state(function ($record) {
                        $attrs = $record->properties['attributes'] ?? [];
                        if (empty($attrs)) return '—';
                        $keys = array_keys($attrs);
                        return implode(', ', array_slice($keys, 0, 4))
                            . (count($keys) > 4 ? ' +' . (count($keys) - 4) : '');
                    })
                    ->color('gray')
                    ->size('sm'),
            ])
            ->filters([
                SelectFilter::make('event')->options([
                    'created' => 'Kreiran',
                    'updated' => 'Ažuriran',
                    'deleted' => 'Obrisan',
                ]),
                SelectFilter::make('subject_type')
                    ->label('Tip modela')
                    ->options(fn () => Activity::query()
                        ->whereNotNull('subject_type')
                        ->distinct()
                        ->pluck('subject_type', 'subject_type')
                        ->mapWithKeys(fn ($v, $k) => [$k => class_basename($v)])
                        ->toArray()),
            ])
            ->recordActions([
                ViewAction::make(),
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
            'index' => ListActivityLogs::route('/'),
            'view'  => ViewActivityLog::route('/{record}'),
        ];
    }
}
