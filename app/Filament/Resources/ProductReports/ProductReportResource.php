<?php

namespace App\Filament\Resources\ProductReports;

use App\Filament\Resources\ProductReports\Pages\ListProductReports;
use App\Filament\Resources\ProductReports\Pages\ViewProductReport;
use App\Models\ProductReport;
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

class ProductReportResource extends Resource
{
    protected static ?string $model = ProductReport::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static string|\UnitEnum|null $navigationGroup = 'Moderacija';

    protected static ?string $navigationLabel = 'Prijave oglasa';

    protected static ?string $modelLabel = 'prijava';

    protected static ?string $pluralModelLabel = 'prijave oglasa';

    protected static ?int $navigationSort = 50;

    public const REASONS = [
        'spam'           => 'Spam / preprodaja',
        'counterfeit'    => 'Falsifikat',
        'inappropriate'  => 'Neprimjeren sadržaj',
        'wrong_category' => 'Pogrešna kategorija',
        'sold_elsewhere' => 'Prodano drugdje',
        'other'          => 'Ostalo',
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

                // ─────── HEADER ROW (12/12)
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
                                    'escalated'  => 'danger',
                                    default      => 'gray',
                                })
                                ->formatStateUsing(fn ($state) => strtoupper($state ?? '')),
                            TextEntry::make('created_at')
                                ->label('Prijavljeno')
                                ->dateTime('d.m.Y. H:i'),
                        ]),
                    ]),

                // ─────── SPLIT: REPORTED PRODUCT (left) | REPORTER CONTEXT (right)

                // LEFT — reported product (7/12)
                Section::make('Prijavljen oglas')
                    ->columnSpan(7)
                    ->compact()
                    ->schema([
                        Grid::make(12)->schema([
                            \Filament\Infolists\Components\ImageEntry::make('product.thumbnail')
                                ->label('')
                                ->square()
                                ->size(140)
                                ->columnSpan(4)
                                ->state(fn ($record) => $record->product?->images->first()?->url),

                            Grid::make(1)
                                ->columnSpan(8)
                                ->schema([
                                    TextEntry::make('product.title')
                                        ->label('')
                                        ->weight('bold')
                                        ->size('lg')
                                        ->url(fn ($record) => $record->product
                                            ? \App\Filament\Resources\Products\ProductResource::getUrl('view', ['record' => $record->product])
                                            : null),
                                    Grid::make(2)->schema([
                                        TextEntry::make('product.brand.name')->label('Brend')->placeholder('—'),
                                        TextEntry::make('product.price')->label('Cijena')->money('BAM'),
                                        TextEntry::make('product.status')
                                            ->label('Status oglasa')
                                            ->badge()
                                            ->color(fn ($state) => match ($state) {
                                                'active' => 'success',
                                                'sold'   => 'gray',
                                                default  => 'warning',
                                            }),
                                        TextEntry::make('product.created_at')->label('Objavljen')->date('d.m.Y.'),
                                    ]),
                                    TextEntry::make('product.seller.username')
                                        ->label('Prodavac')
                                        ->prefix('@')
                                        ->extraAttributes(['class' => 'font-mono text-xs'])
                                        ->color('gray'),
                                ]),
                        ]),
                    ]),

                // RIGHT — reporter context (5/12)
                Section::make('Prijavljivač')
                    ->columnSpan(5)
                    ->compact()
                    ->schema([
                        Grid::make(12)->schema([
                            \Filament\Infolists\Components\ImageEntry::make('reporter.avatar')
                                ->label('')
                                ->circular()
                                ->size(56)
                                ->columnSpan(3)
                                ->defaultImageUrl(fn ($record) =>
                                    'https://ui-avatars.com/api/?name=' . urlencode($record->reporter?->name ?? '?') .
                                    '&background=121212&color=fff&bold=true&size=128'),
                            Grid::make(1)
                                ->columnSpan(9)
                                ->schema([
                                    TextEntry::make('reporter.name')->label('')->weight('semibold'),
                                    TextEntry::make('reporter.username')->label('')->prefix('@')->color('gray')->extraAttributes(['class' => 'font-mono text-xs']),
                                ]),
                        ]),

                        Grid::make(3)->schema([
                            TextEntry::make('reporter_account_age')
                                ->label('Račun aktivan')
                                ->state(fn ($record) => $record->reporter?->created_at?->diffForHumans() ?? '—'),
                            TextEntry::make('reporter_prior_reports')
                                ->label('Prijava ranije')
                                ->state(fn ($record) => $record->reporter
                                    ? \App\Models\ProductReport::where('reporter_id', $record->reporter_id)->count()
                                    : 0)
                                ->extraAttributes(['class' => 'font-mono tabular-nums'])
                                ->color(fn ($state) => (int) $state > 5 ? 'warning' : 'gray'),
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
                                    'dismissed' => 'gray',
                                    'escalated' => 'danger',
                                    default     => 'warning',
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
                TextColumn::make('product.title')
                    ->label('Prijavljen oglas')
                    ->limit(40)
                    ->searchable()
                    ->weight('semibold')
                    ->wrap()
                    ->url(fn ($record) => $record->product
                        ? \App\Filament\Resources\Products\ProductResource::getUrl('view', ['record' => $record->product])
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
                        'escalated'  => 'danger',
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
                    'pending'   => 'Otvorene',
                    'dismissed' => 'Odbačene',
                    'escalated' => 'Eskalirane',
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
                    ->schema([
                        Textarea::make('admin_note')->label('Napomena')->rows(2),
                    ])
                    ->action(function (array $data, $record) {
                        $record->update([
                            'status' => 'dismissed',
                            'admin_note' => $data['admin_note'] ?? null,
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                        Notification::make()->success()->title('Prijava odbačena')->send();
                    }),

                Action::make('escalate')
                    ->label('Eskaliraj (ograniči prodavca)')
                    ->icon('heroicon-m-shield-exclamation')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Eskaliraj prijavu')
                    ->modalDescription('Postavlja listings_require_review = true na prodavcu. Sve buduće objave od ovog korisnika idu u pending_review.')
                    ->action(function ($record) {
                        $seller = $record->product?->seller;
                        if ($seller) {
                            $seller->update(['listings_require_review' => true]);
                        }
                        $record->update([
                            'status' => 'escalated',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                        Notification::make()->success()
                            ->title('Eskalirano')
                            ->body($seller ? "@{$seller->username} je sada flagged za pregled." : 'Prijava ažurirana.')
                            ->send();
                    }),
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
            'index' => ListProductReports::route('/'),
            'view'  => ViewProductReport::route('/{record}'),
        ];
    }
}
