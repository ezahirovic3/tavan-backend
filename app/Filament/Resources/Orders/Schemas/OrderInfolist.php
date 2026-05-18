<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    /** Order state lifecycle in display order — used to colour timeline cells. */
    public const FLOW = ['pending', 'accepted', 'shipped', 'delivered', 'completed'];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([

                // ─────────────────────────────── HERO (12/12)
                Section::make()
                    ->columnSpan(12)
                    ->compact()
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('order_number')
                                ->label('Order ID')
                                ->prefix('#')
                                ->extraAttributes(['class' => 'font-mono text-base font-bold']),
                            TextEntry::make('total')
                                ->label('Iznos')
                                ->money('BAM')
                                ->weight('bold')
                                ->size('lg'),
                            TextEntry::make('status')
                                ->label('Trenutni status')
                                ->badge()
                                ->color(fn ($state) => match ($state) {
                                    'completed','delivered' => 'success',
                                    'declined'              => 'danger',
                                    'shipped'               => 'primary',
                                    'accepted'              => 'info',
                                    'pending'               => 'warning',
                                    default                 => 'gray',
                                })
                                ->formatStateUsing(fn ($state) => ucfirst($state)),
                            TextEntry::make('created_at')
                                ->label('Kreirano')
                                ->dateTime('d.m.Y. H:i')
                                ->extraAttributes(['class' => 'font-mono text-xs']),
                        ]),
                    ]),

                // ─────────────────────────────── TIMELINE (12/12)
                Section::make('Tok narudžbe')
                    ->columnSpan(12)
                    ->compact()
                    ->schema([
                        // Status pill row — every step coloured by whether it's been reached.
                        RepeatableEntry::make('flow_steps')
                            ->label('')
                            ->grid(5)
                            ->state(function ($record) {
                                $currentIdx = array_search($record->status, self::FLOW);
                                if ($currentIdx === false) $currentIdx = -1;
                                $declined = $record->status === 'declined';
                                $timestamps = [
                                    'pending'   => $record->created_at,
                                    'accepted'  => $record->accepted_at ?? null,
                                    'shipped'   => $record->shipped_at  ?? null,
                                    'delivered' => $record->delivered_at?? null,
                                    'completed' => $record->completed_at?? null,
                                ];
                                return collect(self::FLOW)->map(function ($s, $i) use ($currentIdx, $declined, $timestamps) {
                                    return [
                                        'name'  => $s,
                                        'state' => $declined ? 'skipped' : ($i <= $currentIdx ? 'done' : 'pending'),
                                        'when'  => $timestamps[$s]?->format('d.m. H:i') ?? '—',
                                    ];
                                })->toArray();
                            })
                            ->schema([
                                TextEntry::make('name')
                                    ->label(fn ($state, $record) => $record['when'] ?? '—')
                                    ->badge()
                                    ->color(fn ($state, $record) => match ($record['state'] ?? null) {
                                        'done'    => 'success',
                                        'skipped' => 'gray',
                                        default   => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => strtoupper($state))
                                    ->extraAttributes(['class' => 'font-mono text-[10px] tracking-widest']),
                            ]),

                        // Declined banner
                        TextEntry::make('decline_banner')
                            ->label('')
                            ->state('Narudžba je ODBIJENA.')
                            ->badge()
                            ->color('danger')
                            ->icon('heroicon-m-x-circle')
                            ->visible(fn ($record) => $record->status === 'declined'),
                    ]),

                // ─────────────────────────────── BUYER + SELLER CARDS (12/12)
                Section::make('Kupac')
                    ->columnSpan(6)
                    ->compact()
                    ->schema([
                        Grid::make(12)->schema([
                            ImageEntry::make('buyer.avatar')
                                ->label('')
                                ->circular()
                                ->size(56)
                                ->columnSpan(3)
                                ->defaultImageUrl(fn ($record) =>
                                    'https://ui-avatars.com/api/?name=' . urlencode($record->buyer?->name ?? '?') .
                                    '&background=121212&color=fff&bold=true&size=128'),
                            Grid::make(1)
                                ->columnSpan(9)
                                ->schema([
                                    TextEntry::make('buyer.name')->label('')->weight('bold'),
                                    TextEntry::make('buyer.username')->label('')->prefix('@')->color('gray')->extraAttributes(['class' => 'font-mono text-xs']),
                                    TextEntry::make('buyer.rating')
                                        ->label('Rating')
                                        ->numeric(decimalPlaces: 2)
                                        ->icon('heroicon-m-star')
                                        ->iconColor('warning')
                                        ->placeholder('Bez ocjena'),
                                ]),
                        ]),
                    ]),

                Section::make('Prodavac')
                    ->columnSpan(6)
                    ->compact()
                    ->schema([
                        Grid::make(12)->schema([
                            ImageEntry::make('seller.avatar')
                                ->label('')
                                ->circular()
                                ->size(56)
                                ->columnSpan(3)
                                ->defaultImageUrl(fn ($record) =>
                                    'https://ui-avatars.com/api/?name=' . urlencode($record->seller?->name ?? '?') .
                                    '&background=121212&color=fff&bold=true&size=128'),
                            Grid::make(1)
                                ->columnSpan(9)
                                ->schema([
                                    TextEntry::make('seller.name')->label('')->weight('bold'),
                                    TextEntry::make('seller.username')->label('')->prefix('@')->color('gray')->extraAttributes(['class' => 'font-mono text-xs']),
                                    TextEntry::make('seller.rating')
                                        ->label('Rating')
                                        ->numeric(decimalPlaces: 2)
                                        ->icon('heroicon-m-star')
                                        ->iconColor('warning')
                                        ->placeholder('Bez ocjena'),
                                ]),
                        ]),
                    ]),

                // ─────────────────────────────── PRODUCT PREVIEW (8/12)
                Section::make('Oglas')
                    ->columnSpan(8)
                    ->compact()
                    ->schema([
                        Grid::make(12)->schema([
                            ImageEntry::make('product.thumbnail')
                                ->label('')
                                ->square()
                                ->size(96)
                                ->columnSpan(3)
                                ->state(fn ($record) => $record->product?->images->first()?->url),
                            Grid::make(1)
                                ->columnSpan(9)
                                ->schema([
                                    TextEntry::make('product.title')->label('Naslov')->weight('semibold'),
                                    Grid::make(3)->schema([
                                        TextEntry::make('product.brand.name')->label('Brend')->placeholder('—'),
                                        TextEntry::make('product.size')->label('Veličina')->placeholder('—'),
                                        TextEntry::make('product.price')->label('Cijena')->money('BAM'),
                                    ]),
                                    TextEntry::make('product.status')
                                        ->label('Status oglasa')
                                        ->badge()
                                        ->color(fn ($state) => $state === 'sold' ? 'gray' : 'success')
                                        ->formatStateUsing(fn ($state) => match ($state) {
                                            'active' => 'Aktivan',
                                            'sold'   => 'Prodano',
                                            default  => $state ?? '—',
                                        }),
                                ]),
                        ]),
                    ]),

                // ─────────────────────────────── LINKED ENTITIES (4/12)
                Section::make('Povezano')
                    ->columnSpan(4)
                    ->compact()
                    ->schema([
                        TextEntry::make('offer_id')
                            ->label('Iz ponude')
                            ->prefix('#')
                            ->placeholder('Direktna kupovina')
                            ->extraAttributes(['class' => 'font-mono'])
                            ->url(fn ($record) => $record->offer_id ? "/admin/offers/{$record->offer_id}" : null),
                        TextEntry::make('trade_id')
                            ->label('Iz trade-a')
                            ->prefix('#')
                            ->placeholder('—')
                            ->extraAttributes(['class' => 'font-mono'])
                            ->url(fn ($record) => $record->trade_id ? "/admin/trades/{$record->trade_id}" : null),
                        TextEntry::make('conversation_id')
                            ->label('Razgovor')
                            ->prefix('#')
                            ->placeholder('—')
                            ->extraAttributes(['class' => 'font-mono']),
                        TextEntry::make('shippingOption.name')
                            ->label('Dostava')
                            ->placeholder('—'),
                    ]),

                // ─────────────────────────────── AUDIT (12/12)
                Section::make('Audit trail')
                    ->columnSpan(12)
                    ->compact()
                    ->collapsed()
                    ->schema([
                        RepeatableEntry::make('audit_log')
                            ->label('')
                            ->state(fn ($record) => \Spatie\Activitylog\Models\Activity::query()
                                ->where('subject_type', \App\Models\Order::class)
                                ->where('subject_id', $record->id)
                                ->latest()
                                ->limit(20)
                                ->get())
                            ->schema([
                                Grid::make(4)->schema([
                                    TextEntry::make('created_at')->label('Vrijeme')->dateTime('d.m.Y. H:i:s')->extraAttributes(['class' => 'font-mono text-xs']),
                                    TextEntry::make('causer.name')->label('Admin')->placeholder('Sistem'),
                                    TextEntry::make('event')->label('Akcija')->badge()
                                        ->color(fn ($state) => match ($state) {
                                            'created' => 'success',
                                            'updated' => 'warning',
                                            'deleted' => 'danger',
                                            default   => 'gray',
                                        }),
                                    TextEntry::make('description')->label('Opis')->limit(60),
                                ]),
                            ])
                            ->placeholder('Nema zabilježenih radnji administratora.'),
                    ]),
            ]);
    }
}
