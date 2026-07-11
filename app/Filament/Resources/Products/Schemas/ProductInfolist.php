<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([

                // ─────────────────────────────── HERO
                // Gallery (left, 7/12)  |  Title + price + status (right, 5/12)
                Section::make()
                    ->columnSpan(7)
                    ->compact()
                    ->schema([
                        ImageEntry::make('hero_image')
                            ->label('')
                            ->state(fn ($record) => $record->images->first()?->url)
                            ->stacked(false)
                            ->square()
                            ->size(420)
                            ->limit(1)
                            ->extraImgAttributes(['class' => 'object-cover w-full']),

                        // Thumbnail strip below hero image
                        ImageEntry::make('thumbnail_strip')
                            ->label('')
                            ->state(fn ($record) => $record->images->pluck('url')->toArray())
                            ->stacked(false)
                            ->square()
                            ->size(72)
                            ->limit(7)
                            ->limitedRemainingText()
                            ->extraImgAttributes(['class' => 'object-cover']),
                    ]),

                Section::make()
                    ->columnSpan(5)
                    ->compact()
                    ->schema([
                        TextEntry::make('title')
                            ->label('')
                            ->weight('bold')
                            ->size('xl')
                            ->extraAttributes(['style' => 'font-family:Archivo,Inter,sans-serif;font-weight:800;font-size:28px;letter-spacing:-0.02em;line-height:1.1']),

                        Grid::make(2)->schema([
                            TextEntry::make('price')
                                ->label('Cijena')
                                ->money('BAM')
                                ->size('lg')
                                ->weight('bold'),
                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->color(fn ($state) => match ($state) {
                                    'active'         => 'success',
                                    'pending_review' => 'primary',
                                    'reserved'       => 'info',
                                    'sold'           => 'gray',
                                    'draft'          => 'warning',
                                    default          => 'gray',
                                })
                                ->formatStateUsing(fn ($state) => match ($state) {
                                    'active'         => 'Aktivan',
                                    'pending_review' => 'Na pregledu',
                                    'reserved'       => 'Rezervisan',
                                    'sold'           => 'Prodano',
                                    'draft'          => 'Draft',
                                    default          => $state,
                                }),
                        ]),

                        TextEntry::make('description')
                            ->label('Opis')
                            ->prose()
                            ->placeholder('—'),
                    ]),

                // ─────────────────────────────── METADATA STRIP (12/12)
                Section::make('Detalji')
                    ->columnSpan(12)
                    ->compact()
                    ->schema([
                        Grid::make(6)->schema([
                            TextEntry::make('brand.name')->label('Brend')->placeholder('—'),
                            TextEntry::make('category')->label('Kategorija')->placeholder('—'),
                            TextEntry::make('size')->label('Veličina')->placeholder('—'),
                            TextEntry::make('condition')
                                ->label('Stanje')
                                ->formatStateUsing(fn ($state) => match ($state) {
                                    'new_with_tags' => 'Novo s etiketom',
                                    'like_new'      => 'Kao novo',
                                    'good'          => 'Dobro',
                                    'fair'          => 'Solidno',
                                    default         => $state ?? '—',
                                }),
                            TextEntry::make('color')->label('Boja')->placeholder('—'),
                            TextEntry::make('styles')
                                ->label('Stilovi')
                                ->placeholder('—')
                                ->formatStateUsing(fn ($state) => \App\Enums\ProductStyle::tryFrom($state)?->getLabel() ?? $state)
                                ->badge(),
                            TextEntry::make('created_at')->label('Objavljen')->date('d.m.Y.'),
                        ]),
                    ]),

                // ─────────────────────────────── SELLER CARD (8/12)
                Section::make('Prodavac')
                    ->columnSpan(8)
                    ->compact()
                    ->schema([
                        Grid::make(12)->schema([
                            ImageEntry::make('seller.avatar')
                                ->label('')
                                ->circular()
                                ->size(64)
                                ->columnSpan(2)
                                ->defaultImageUrl(fn ($record) =>
                                    'https://ui-avatars.com/api/?name=' . urlencode($record->seller?->name ?? '?') .
                                    '&background=121212&color=fff&bold=true&size=128'),

                            Grid::make(1)
                                ->columnSpan(6)
                                ->schema([
                                    TextEntry::make('seller.name')
                                        ->label('')
                                        ->weight('bold')
                                        ->size('lg'),
                                    TextEntry::make('seller.username')
                                        ->label('')
                                        ->prefix('@')
                                        ->color('gray')
                                        ->extraAttributes(['class' => 'font-mono text-xs']),
                                    TextEntry::make('seller.city')
                                        ->label('')
                                        ->color('gray')
                                        ->icon('heroicon-m-map-pin')
                                        ->placeholder('Grad nije postavljen'),
                                ]),

                            Grid::make(1)
                                ->columnSpan(4)
                                ->schema([
                                    TextEntry::make('seller.rating')
                                        ->label('Rating')
                                        ->numeric(decimalPlaces: 2)
                                        ->icon('heroicon-m-star')
                                        ->iconColor('warning')
                                        ->placeholder('Bez ocjena'),
                                    TextEntry::make('seller.listings_require_review')
                                        ->label('Auto-review')
                                        ->badge()
                                        ->formatStateUsing(fn ($state) => $state ? 'FLAGGED' : 'OK')
                                        ->color(fn ($state) => $state ? 'danger' : 'gray')
                                        ->extraAttributes(['class' => 'font-mono text-[10px] tracking-widest']),
                                    TextEntry::make('seller.deletion_requested_at')
                                        ->label('Status računa')
                                        ->badge()
                                        ->formatStateUsing(fn ($state) => $state
                                            ? 'Briše se ' . \Carbon\Carbon::parse($state)->addDays(30)->format('d.m.Y.')
                                            : 'Aktivan')
                                        ->color(fn ($state) => $state ? 'danger' : 'success'),
                                ]),
                        ]),

                        // Seller stat row
                        Grid::make(4)->schema([
                            TextEntry::make('seller_listings_count')
                                ->label('Ukupno oglasa')
                                ->state(fn ($record) => $record->seller?->products()->count() ?? 0)
                                ->extraAttributes(['class' => 'font-mono tabular-nums']),
                            TextEntry::make('seller_sold_count')
                                ->label('Prodano')
                                ->state(fn ($record) => $record->seller?->products()->where('status', 'sold')->count() ?? 0)
                                ->extraAttributes(['class' => 'font-mono tabular-nums']),
                            TextEntry::make('seller_reports_count')
                                ->label('Prijava primljeno')
                                ->state(fn ($record) => $record->seller_id
                                    ? \App\Models\UserReport::where('reported_id', $record->seller_id)->count()
                                    : 0)
                                ->color(fn ($state) => (int) $state > 0 ? 'danger' : 'gray')
                                ->extraAttributes(['class' => 'font-mono tabular-nums']),
                            TextEntry::make('seller_joined')
                                ->label('Pridružio se')
                                ->state(fn ($record) => $record->seller?->created_at?->format('d.m.Y.') ?? '—'),
                        ]),
                    ]),

                // ─────────────────────────────── MOD QUEUE WIDGET (4/12)
                Section::make('Status oglasa')
                    ->columnSpan(4)
                    ->compact()
                    ->schema([
                        TextEntry::make('id')
                            ->label('Product ID')
                            ->prefix('#')
                            ->extraAttributes(['class' => 'font-mono']),

                        TextEntry::make('rejection_reason')
                            ->label('Razlog odbijanja')
                            ->placeholder('Nije bilo odbijanja')
                            ->color(fn ($state) => $state ? 'danger' : 'gray')
                            ->prose(),

                        TextEntry::make('rejected_at')
                            ->label('Datum odbijanja')
                            ->dateTime('d.m.Y. H:i')
                            ->placeholder('—'),

                        TextEntry::make('admin_note')
                            ->label('Admin napomena')
                            ->placeholder('—')
                            ->prose(),
                    ]),

                // ─────────────────────────────── VINTAGE (12/12)
                Section::make('Vintage')
                    ->columnSpan(12)
                    ->compact()
                    ->visible(fn ($record) => $record->vintage_status !== null)
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('vintage_status')
                                ->label('Status')
                                ->badge()
                                ->color(fn ($state) => match ($state) {
                                    'approved' => 'warning',
                                    'pending'  => 'gray',
                                    'rejected' => 'danger',
                                    default    => 'gray',
                                })
                                ->formatStateUsing(fn ($state) => match ($state) {
                                    'approved' => '✦ Odobreno',
                                    'pending'  => 'Na čekanju',
                                    'rejected' => 'Odbijeno',
                                    default    => $state,
                                }),

                            TextEntry::make('vintage_era')
                                ->label('Era')
                                ->formatStateUsing(fn ($state) => match ($state) {
                                    '50s' => '1950s',
                                    '60s' => '1960s',
                                    '70s' => '1970s',
                                    '80s' => '1980s',
                                    '90s' => '1990s',
                                    'y2k' => 'Y2K (2000s)',
                                    default => $state ?? '—',
                                })
                                ->placeholder('—'),

                            TextEntry::make('vintage_reviewed_at')
                                ->label('Pregledano')
                                ->dateTime('d.m.Y. H:i')
                                ->placeholder('—'),

                            TextEntry::make('vintage_reject_reason')
                                ->label('Razlog odbijanja')
                                ->color('danger')
                                ->placeholder('—'),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('vintage_notes')
                                ->label('Opis (od prodavca)')
                                ->prose()
                                ->placeholder('—'),

                            TextEntry::make('vintage_provenance')
                                ->label('Porijeklo (od prodavca)')
                                ->prose()
                                ->placeholder('—'),
                        ]),
                    ]),

                // ─────────────────────────────── TABS: ORDERS / REPORTS / ACTIVITY (12/12)
                Tabs::make('Povezano')
                    ->columnSpan(12)
                    ->tabs([
                        Tab::make('Narudžbe')
                            ->icon('heroicon-m-shopping-bag')
                            ->badge(fn ($record) => $record->orders()->count())
                            ->schema([
                                RepeatableEntry::make('orders')
                                    ->label('')
                                    ->grid(1)
                                    ->schema([
                                        Grid::make(4)->schema([
                                            TextEntry::make('order_number')
                                                ->label('ID')
                                                ->prefix('#')
                                                ->extraAttributes(['class' => 'font-mono']),
                                            TextEntry::make('buyer.username')
                                                ->label('Kupac')
                                                ->prefix('@')
                                                ->extraAttributes(['class' => 'font-mono text-xs']),
                                            TextEntry::make('status')
                                                ->label('Status')
                                                ->badge()
                                                ->color(fn ($state) => match ($state) {
                                                    'completed', 'delivered' => 'success',
                                                    'declined'               => 'danger',
                                                    'shipped'                => 'primary',
                                                    default                  => 'gray',
                                                }),
                                            TextEntry::make('created_at')
                                                ->label('Datum')
                                                ->date('d.m.Y.'),
                                        ]),
                                    ])
                                    ->placeholder('Nema narudžbi za ovaj oglas.'),
                            ]),

                        Tab::make('Prijave')
                            ->icon('heroicon-m-flag')
                            ->badge(fn ($record) => $record->reports()->count())
                            ->badgeColor(fn ($record) => $record->reports()->where('status', 'pending')->exists() ? 'danger' : 'gray')
                            ->schema([
                                RepeatableEntry::make('reports')
                                    ->label('')
                                    ->schema([
                                        Grid::make(4)->schema([
                                            TextEntry::make('reporter.username')
                                                ->label('Prijavio')
                                                ->prefix('@')
                                                ->extraAttributes(['class' => 'font-mono text-xs']),
                                            TextEntry::make('reason')
                                                ->label('Razlog')
                                                ->badge()
                                                ->color('warning'),
                                            TextEntry::make('status')
                                                ->label('Status')
                                                ->badge()
                                                ->color(fn ($state) => match ($state) {
                                                    'pending'   => 'warning',
                                                    'escalated' => 'danger',
                                                    default     => 'gray',
                                                }),
                                            TextEntry::make('created_at')->label('Datum')->date('d.m.Y.'),
                                        ]),
                                        TextEntry::make('description')
                                            ->label('Opis')
                                            ->prose()
                                            ->placeholder('—'),
                                    ])
                                    ->placeholder('Nema prijava — čist oglas.'),
                            ]),

                        Tab::make('Aktivnost')
                            ->icon('heroicon-m-clipboard-document-list')
                            ->schema([
                                RepeatableEntry::make('activities')
                                    ->label('')
                                    ->state(fn ($record) => \Spatie\Activitylog\Models\Activity::query()
                                        ->where('subject_type', \App\Models\Product::class)
                                        ->where('subject_id', $record->id)
                                        ->latest()
                                        ->limit(20)
                                        ->get())
                                    ->schema([
                                        Grid::make(4)->schema([
                                            TextEntry::make('created_at')
                                                ->label('Vrijeme')
                                                ->dateTime('d.m.Y. H:i')
                                                ->extraAttributes(['class' => 'font-mono text-xs']),
                                            TextEntry::make('causer.name')
                                                ->label('Admin')
                                                ->placeholder('Sistem'),
                                            TextEntry::make('event')
                                                ->label('Akcija')
                                                ->badge()
                                                ->color(fn ($state) => match ($state) {
                                                    'created' => 'success',
                                                    'updated' => 'warning',
                                                    'deleted' => 'danger',
                                                    default   => 'gray',
                                                }),
                                            TextEntry::make('description')->label('Opis')->limit(40),
                                        ]),
                                    ])
                                    ->placeholder('Nema zapisa o aktivnosti.'),
                            ]),
                    ]),
            ]);
    }
}
