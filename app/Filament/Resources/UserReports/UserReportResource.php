<?php

namespace App\Filament\Resources\UserReports;

use App\Filament\Resources\UserReports\Pages\ListUserReports;
use App\Filament\Resources\UserReports\Pages\ViewUserReport;
use App\Models\UserReport;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UserReportResource extends Resource
{
    protected static ?string $model = UserReport::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-minus';

    protected static string|\UnitEnum|null $navigationGroup = 'Moderacija';

    protected static ?string $navigationLabel = 'Prijave korisnika';

    protected static ?string $modelLabel = 'prijava';

    protected static ?string $pluralModelLabel = 'prijave korisnika';

    protected static ?int $navigationSort = 51;

    public const REASONS = [
        'harassment'   => 'Uznemiravanje',
        'scam'         => 'Prevara',
        'fake_account' => 'Lažni profil',
        'spam'         => 'Spam',
        'inappropriate'=> 'Neprimjeren sadržaj',
        'other'        => 'Ostalo',
    ];

    public static function getNavigationBadge(): ?string
    {
        $n = static::getModel()::where('status', 'pending')->count();
        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([

                // ─────── HEADER (12/12)
                Section::make()
                    ->columnSpan(12)
                    ->compact()
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('id')
                                ->label('Report ID')
                                ->prefix('#')
                                ->extraAttributes(['class' => 'font-mono']),
                            TextEntry::make('reason')
                                ->label('Razlog')
                                ->badge()
                                ->color('warning')
                                ->formatStateUsing(fn ($state) => self::REASONS[$state] ?? $state),
                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->color(fn ($state) => match ($state) {
                                    'pending'    => 'warning',
                                    'dismissed'  => 'gray',
                                    'warned'     => 'warning',
                                    'restricted' => 'danger',
                                    'banned'     => 'danger',
                                    default      => 'gray',
                                })
                                ->formatStateUsing(fn ($state) => strtoupper($state ?? '')),
                            TextEntry::make('created_at')
                                ->label('Prijavljeno')
                                ->dateTime('d.m.Y. H:i'),
                        ]),
                    ]),

                // ─────── SPLIT: REPORTED USER (left) | REPORTER (right)

                Section::make('Prijavljeni korisnik')
                    ->columnSpan(6)
                    ->compact()
                    ->schema([
                        Grid::make(12)->schema([
                            \Filament\Infolists\Components\ImageEntry::make('reported.avatar')
                                ->label('')
                                ->circular()
                                ->size(64)
                                ->columnSpan(3)
                                ->defaultImageUrl(fn ($record) =>
                                    'https://ui-avatars.com/api/?name=' . urlencode($record->reported?->name ?? '?') .
                                    '&background=121212&color=fff&bold=true&size=128'),
                            Grid::make(1)
                                ->columnSpan(9)
                                ->schema([
                                    TextEntry::make('reported.name')
                                        ->label('')
                                        ->weight('bold')
                                        ->url(fn ($record) => $record->reported
                                            ? \App\Filament\Resources\Users\UserResource::getUrl('view', ['record' => $record->reported])
                                            : null),
                                    TextEntry::make('reported.username')
                                        ->label('')
                                        ->prefix('@')
                                        ->color('gray')
                                        ->extraAttributes(['class' => 'font-mono text-xs']),
                                ]),
                        ]),

                        Grid::make(3)->schema([
                            TextEntry::make('reported_listings_count')
                                ->label('Oglasa')
                                ->state(fn ($record) => $record->reported?->products()->count() ?? 0)
                                ->extraAttributes(['class' => 'font-mono tabular-nums']),
                            TextEntry::make('reported_reports_count')
                                ->label('Prijava primljeno')
                                ->state(fn ($record) => $record->reported ? \App\Models\UserReport::where('reported_id', $record->reported->id)->count() : 0)
                                ->color(fn ($state) => (int) $state > 3 ? 'danger' : 'gray')
                                ->extraAttributes(['class' => 'font-mono tabular-nums']),
                            TextEntry::make('reported_auto_review')
                                ->label('Auto-review')
                                ->state(fn ($record) => $record->reported?->listings_require_review ? 'FLAGGED' : 'OK')
                                ->badge()
                                ->color(fn ($state) => $state === 'FLAGGED' ? 'danger' : 'gray')
                                ->extraAttributes(['class' => 'font-mono text-[10px] tracking-widest']),
                        ]),
                    ]),

                Section::make('Prijavljivač')
                    ->columnSpan(6)
                    ->compact()
                    ->schema([
                        Grid::make(12)->schema([
                            \Filament\Infolists\Components\ImageEntry::make('reporter.avatar')
                                ->label('')
                                ->circular()
                                ->size(64)
                                ->columnSpan(3)
                                ->defaultImageUrl(fn ($record) =>
                                    'https://ui-avatars.com/api/?name=' . urlencode($record->reporter?->name ?? '?') .
                                    '&background=121212&color=fff&bold=true&size=128'),
                            Grid::make(1)
                                ->columnSpan(9)
                                ->schema([
                                    TextEntry::make('reporter.name')->label('')->weight('bold'),
                                    TextEntry::make('reporter.username')->label('')->prefix('@')->color('gray')->extraAttributes(['class' => 'font-mono text-xs']),
                                ]),
                        ]),

                        Grid::make(3)->schema([
                            TextEntry::make('reporter_age')
                                ->label('Račun aktivan')
                                ->state(fn ($record) => $record->reporter?->created_at?->diffForHumans() ?? '—'),
                            TextEntry::make('reporter_prior')
                                ->label('Prijava ranije')
                                ->state(fn ($record) => $record->reporter
                                    ? \App\Models\UserReport::where('reporter_id', $record->reporter_id)->count()
                                    : 0)
                                ->color(fn ($state) => (int) $state > 5 ? 'warning' : 'gray')
                                ->extraAttributes(['class' => 'font-mono tabular-nums']),
                            TextEntry::make('reporter_rating')
                                ->label('Rating')
                                ->state(fn ($record) => $record->reporter?->rating
                                    ? number_format($record->reporter->rating, 2)
                                    : '—')
                                ->icon('heroicon-m-star')
                                ->iconColor('warning'),
                        ]),
                    ]),

                // ─────── DESCRIPTION (12/12)
                Section::make('Opis prijave')
                    ->columnSpan(12)
                    ->compact()
                    ->schema([
                        TextEntry::make('description')
                            ->label('')
                            ->prose()
                            ->placeholder('Bez dodatnog opisa.'),
                    ]),

                // ─────── ADMIN DECISION (12/12)
                Section::make('Odluka administratora')
                    ->columnSpan(12)
                    ->compact()
                    ->visible(fn ($record) => $record->status !== 'pending')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('reviewed_by_user.name')
                                ->label('Riješio')
                                ->state(fn ($record) => $record->reviewedBy?->name ?? '—'),
                            TextEntry::make('reviewed_at')
                                ->label('Datum odluke')
                                ->dateTime('d.m.Y. H:i')
                                ->placeholder('—'),
                            TextEntry::make('status')
                                ->label('Ishod')
                                ->badge()
                                ->color(fn ($state) => match ($state) {
                                    'restricted','banned' => 'danger',
                                    'warned'              => 'warning',
                                    default               => 'gray',
                                }),
                        ]),
                        TextEntry::make('admin_note')
                            ->label('Napomena administratora')
                            ->prose()
                            ->placeholder('—'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reported.username')
                    ->label('Prijavljeni korisnik')
                    ->prefix('@')
                    ->searchable()
                    ->weight('semibold')
                    ->extraAttributes(['class' => 'font-mono'])
                    ->url(fn ($record) => $record->reported
                        ? \App\Filament\Resources\Users\UserResource::getUrl('view', ['record' => $record->reported])
                        : null),

                TextColumn::make('reporter.username')
                    ->label('Prijavio')
                    ->prefix('@')
                    ->color('gray')
                    ->extraAttributes(['class' => 'font-mono text-xs']),

                TextColumn::make('reason')
                    ->label('Razlog')
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn ($state) => self::REASONS[$state] ?? $state),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending'    => 'warning',
                        'dismissed'  => 'gray',
                        'warned'     => 'warning',
                        'restricted' => 'danger',
                        'banned'     => 'danger',
                        default      => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Datum')
                    ->date('d.m.Y.')
                    ->color('gray')
                    ->size('sm')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending'    => 'Otvorene',
                    'dismissed'  => 'Odbačene',
                    'warned'     => 'Upozorene',
                    'restricted' => 'Ograničene',
                    'banned'     => 'Banane',
                ])->default('pending'),
                SelectFilter::make('reason')->options(self::REASONS),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('dismiss')
                    ->label('Odbaci')
                    ->icon('heroicon-m-x-mark')
                    ->color('gray')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(fn ($record) => $record->update([
                        'status' => 'dismissed',
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => now(),
                    ])),

                Action::make('warn')
                    ->label('Upozori (placeholder)')
                    ->icon('heroicon-m-exclamation-triangle')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->disabled() // future feature, per brief
                    ->action(fn () => null),

                Action::make('restrict')
                    ->label('Ograniči korisnika')
                    ->icon('heroicon-m-shield-exclamation')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->schema([
                        Textarea::make('admin_note')->label('Napomena')->rows(2),
                    ])
                    ->modalHeading('Ograniči korisnika')
                    ->modalDescription('listings_require_review postaje true.')
                    ->action(function (array $data, $record) {
                        if ($record->reported) {
                            $record->reported->update(['listings_require_review' => true]);
                        }
                        $record->update([
                            'status' => 'restricted',
                            'admin_note' => $data['admin_note'] ?? null,
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                        Notification::make()->success()->title('Korisnik ograničen')->send();
                    }),

                Action::make('ban')
                    ->label('Ban (placeholder)')
                    ->icon('heroicon-m-no-symbol')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->disabled() // future feature, per brief
                    ->action(fn () => null),
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
            'index' => ListUserReports::route('/'),
            'view'  => ViewUserReport::route('/{record}'),
        ];
    }
}
