<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static \UnitEnum|string|null $navigationGroup = 'Sistem';
    protected static ?int $navigationSort = 1;
    protected static ?string $label = 'Log aktivnosti';
    protected static ?string $pluralLabel = 'Log aktivnosti';

    // ── Permissions ───────────────────────────────────────────────────────────

    public static function canCreate(): bool               { return false; }
    public static function canEdit(Model $record): bool    { return false; }
    public static function canDelete(Model $record): bool  { return auth()->user()?->isSuperAdmin() ?? false; }
    public static function canDeleteAny(): bool            { return auth()->user()?->isSuperAdmin() ?? false; }

    // ── Form (unused) ─────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    // ── Infolist (detail view) ─────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Detalji')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('created_at')->label('Datum/vrijeme')->dateTime('d.m.Y H:i:s'),
                    TextEntry::make('causer.name')->label('Admin')->default('—'),
                    TextEntry::make('event')
                        ->label('Akcija')
                        ->badge()
                        ->color(fn (?string $state): string => match ($state) {
                            'created' => 'success',
                            'updated' => 'warning',
                            'deleted' => 'danger',
                            default   => 'gray',
                        })
                        ->formatStateUsing(fn (?string $state): string => match ($state) {
                            'created' => 'Kreiran',
                            'updated' => 'Ažuriran',
                            'deleted' => 'Obrisan',
                            default   => $state ?? '—',
                        }),
                ]),
                Grid::make(2)->schema([
                    TextEntry::make('subject_type')
                        ->label('Model')
                        ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—'),
                    TextEntry::make('subject_id')->label('ID zapisa'),
                ]),
            ]),

            Section::make('Stare vrijednosti')
                ->schema([
                    KeyValueEntry::make('old_values')
                        ->label('')
                        ->state(fn (Activity $record) => $record->attribute_changes?->get('old') ?? []),
                ])
                ->visible(fn (Activity $record) => ! empty($record->attribute_changes?->get('old') ?? [])),

            Section::make('Nove vrijednosti')
                ->schema([
                    KeyValueEntry::make('new_values')
                        ->label('')
                        ->state(fn (Activity $record) => $record->attribute_changes?->get('attributes') ?? []),
                ])
                ->visible(fn (Activity $record) => ! empty($record->attribute_changes?->get('attributes') ?? [])),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Kada')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),

                TextColumn::make('causer.name')
                    ->label('Admin')
                    ->default('—')
                    ->searchable(),

                TextColumn::make('event')
                    ->label('Akcija')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'created' => 'Kreiran',
                        'updated' => 'Ažuriran',
                        'deleted' => 'Obrisan',
                        default   => $state ?? '—',
                    }),

                TextColumn::make('subject_type')
                    ->label('Model')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—'),

                TextColumn::make('subject_label')
                    ->label('Zapis')
                    ->state(function (Activity $record): string {
                        $subject = $record->subject;
                        if (! $subject) {
                            return "#{$record->subject_id}";
                        }
                        return match (true) {
                            isset($subject->name)     => $subject->name,
                            isset($subject->title)    => $subject->title,
                            isset($subject->username) => "@{$subject->username}",
                            isset($subject->email)    => $subject->email,
                            default                   => "#{$subject->getKey()}",
                        };
                    }),

                TextColumn::make('changes_summary')
                    ->label('Polja')
                    ->state(function (Activity $record): string {
                        $changes = $record->attribute_changes;
                        $old = $changes?->get('old') ?? [];
                        $new = $changes?->get('attributes') ?? [];
                        $keys = array_unique(array_merge(array_keys((array) $old), array_keys((array) $new)));
                        return empty($keys) ? '—' : implode(', ', $keys);
                    })
                    ->wrap()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label('Akcija')
                    ->options([
                        'created' => 'Kreiran',
                        'updated' => 'Ažuriran',
                        'deleted' => 'Obrisan',
                    ]),

                SelectFilter::make('subject_type')
                    ->label('Model')
                    ->options([
                        'App\\Models\\User'            => 'User',
                        'App\\Models\\Product'         => 'Product',
                        'App\\Models\\Brand'           => 'Brand',
                        'App\\Models\\BlogPost'        => 'BlogPost',
                        'App\\Models\\BrandSuggestion' => 'BrandSuggestion',
                        'App\\Models\\UserReport'      => 'UserReport',
                        'App\\Models\\ProductReport'   => 'ProductReport',
                        'App\\Models\\SupportInquiry'  => 'SupportInquiry',
                    ]),
            ])
            ->actions([ViewAction::make()])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view'  => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
}
