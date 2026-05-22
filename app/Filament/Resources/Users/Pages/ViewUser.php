<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn ($record) => ! UserResource::isLocked($record)),

            Action::make('toggleVerified')
                ->label(fn ($record) => $record->is_verified ? 'Skini verifikaciju' : 'Verifikuj')
                ->icon(fn ($record) => $record->is_verified ? 'heroicon-m-x-circle' : 'heroicon-m-check-badge')
                ->color(fn ($record) => $record->is_verified ? 'gray' : 'success')
                ->visible(fn ($record) => ! UserResource::isLocked($record))
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->update(['is_verified' => ! $record->is_verified]);
                    Notification::make()->success()->title('Status verifikacije ažuriran')->send();
                }),

            Action::make('toggleAutoReview')
                ->label(fn ($record) => $record->listings_require_review ? 'Ukloni auto-review' : 'Postavi auto-review')
                ->icon('heroicon-m-shield-exclamation')
                ->color(fn ($record) => $record->listings_require_review ? 'gray' : 'danger')
                ->visible(fn ($record) => ! UserResource::isLocked($record))
                ->requiresConfirmation()
                ->modalDescription('Svaki novi oglas korisnika će ići u pending_review.')
                ->action(function ($record) {
                    $record->update(['listings_require_review' => ! $record->listings_require_review]);
                    Notification::make()->success()->title('Auto-review flag ažuriran')->send();
                }),

            Action::make('cancelDeletion')
                ->label('Poništi brisanje')
                ->icon('heroicon-m-arrow-uturn-left')
                ->color('success')
                ->visible(fn ($record) => auth()->user()->isSuperAdmin() && $record->deletion_requested_at)
                ->requiresConfirmation()
                ->modalHeading('Poništi brisanje računa')
                ->modalDescription('Datum brisanja će biti uklonjen i račun postaje aktivan.')
                ->action(function ($record) {
                    $record->update(['deletion_requested_at' => null]);
                    Notification::make()->success()->title('Brisanje poništeno')->send();
                }),

            Action::make('forceAnonymize')
                ->label('Odmah obriši')
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->visible(fn ($record) => auth()->user()->isSuperAdmin() && ! UserResource::isLocked($record))
                ->requiresConfirmation()
                ->modalHeading('Trajno obriši račun')
                ->modalDescription('Ovo će odmah anonimizirati ovaj račun i obrisati sve podatke. Akcija je nepovratna.')
                ->action(function ($record) {
                    app(\App\Services\UserDeletionService::class)->anonymize($record);
                    Notification::make()->success()->title('Račun je trajno obrisan')->send();
                    return redirect(UserResource::getUrl('index'));
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([

                // ─────────────────────────────── HERO (12/12)
                Section::make()
                    ->columnSpan(12)
                    ->compact()
                    ->schema([
                        Grid::make(12)->schema([

                            // Avatar
                            ImageEntry::make('avatar')
                                ->label('')
                                ->circular()
                                ->size(96)
                                ->columnSpan(2)
                                ->defaultImageUrl(fn ($record) =>
                                    'https://ui-avatars.com/api/?name=' . urlencode($record->name ?? '?') .
                                    '&background=121212&color=fff&bold=true&size=256'),

                            // Identity
                            Grid::make(1)
                                ->columnSpan(5)
                                ->schema([
                                    TextEntry::make('name')
                                        ->label('')
                                        ->weight('bold')
                                        ->size('xl')
                                        ->extraAttributes(['style' => 'font-family:Archivo,Inter,sans-serif;font-weight:800;font-size:28px;letter-spacing:-0.02em;line-height:1.1']),
                                    TextEntry::make('username')
                                        ->label('')
                                        ->prefix('@')
                                        ->color('gray')
                                        ->copyable()
                                        ->extraAttributes(['class' => 'font-mono']),
                                    TextEntry::make('email')
                                        ->label('')
                                        ->icon('heroicon-m-envelope')
                                        ->color('gray')
                                        ->copyable(),
                                ]),

                            // Role + flags
                            Grid::make(1)
                                ->columnSpan(5)
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextEntry::make('role')
                                            ->label('Role')
                                            ->badge()
                                            ->color(fn ($state) => UserResource::roleColor($state))
                                            ->formatStateUsing(fn ($state) => strtoupper(str_replace('_', ' ', $state)))
                                            ->extraAttributes(['class' => 'font-mono text-[10px] tracking-widest']),
                                        IconEntry::make('is_verified')
                                            ->label('Verificiran')
                                            ->boolean()
                                            ->trueIcon('heroicon-m-check-badge')
                                            ->trueColor('success')
                                            ->falseIcon('heroicon-m-minus-circle')
                                            ->falseColor('gray'),
                                        IconEntry::make('listings_require_review')
                                            ->label('Auto-review flag')
                                            ->boolean()
                                            ->trueIcon('heroicon-m-shield-exclamation')
                                            ->trueColor('danger')
                                            ->falseIcon('heroicon-m-minus-circle')
                                            ->falseColor('gray'),
                                        TextEntry::make('location')
                                            ->label('Grad')
                                            ->icon('heroicon-m-map-pin')
                                            ->placeholder('—'),
                                        TextEntry::make('account_status_badge')
                                            ->label('Status računa')
                                            ->badge()
                                            ->columnSpan(2)
                                            ->state(fn ($record) => match (true) {
                                                $record->is_anonymized                => 'anonymized',
                                                (bool) $record->deletion_requested_at => 'pending_deletion',
                                                default                               => 'active',
                                            })
                                            ->formatStateUsing(fn ($state, $record) => match ($state) {
                                                'anonymized'       => 'Obrisan',
                                                'pending_deletion' => 'Briše se ' . \Carbon\Carbon::parse($record->deletion_requested_at)->addDays(30)->format('d.m.Y.'),
                                                default            => 'Aktivan',
                                            })
                                            ->color(fn ($state) => match ($state) {
                                                'anonymized'       => 'gray',
                                                'pending_deletion' => 'danger',
                                                default            => 'success',
                                            }),
                                    ]),
                                ]),
                        ]),
                    ]),

                // ─────────────────────────────── STAT STRIP (12/12)
                Section::make('Statistika')
                    ->columnSpan(12)
                    ->compact()
                    ->schema([
                        Grid::make(6)->schema([
                            TextEntry::make('stat_listings')
                                ->label('Oglasa')
                                ->state(fn ($record) => $record->products()->count())
                                ->extraAttributes(['class' => 'font-mono tabular-nums text-2xl font-bold']),
                            TextEntry::make('stat_active')
                                ->label('Aktivnih')
                                ->state(fn ($record) => $record->products()->where('status', 'active')->count())
                                ->color('success')
                                ->extraAttributes(['class' => 'font-mono tabular-nums text-2xl font-bold']),
                            TextEntry::make('stat_sold')
                                ->label('Prodano')
                                ->state(fn ($record) => $record->products()->where('status', 'sold')->count())
                                ->extraAttributes(['class' => 'font-mono tabular-nums text-2xl font-bold']),
                            TextEntry::make('stat_buyer')
                                ->label('Kupovine')
                                ->state(fn ($record) => $record->ordersAsBuyer()->count())
                                ->extraAttributes(['class' => 'font-mono tabular-nums text-2xl font-bold']),
                            TextEntry::make('stat_seller')
                                ->label('Prodaje')
                                ->state(fn ($record) => $record->ordersAsSeller()->count())
                                ->extraAttributes(['class' => 'font-mono tabular-nums text-2xl font-bold']),
                            TextEntry::make('stat_rating')
                                ->label('Rating')
                                ->state(fn ($record) => $record->rating ? number_format($record->rating, 2) : '—')
                                ->suffix(fn ($record) => $record->total_reviews ? " · {$record->total_reviews} ocj." : null)
                                ->color(fn ($state) => $state === '—' ? 'gray' : 'warning')
                                ->icon('heroicon-m-star')
                                ->extraAttributes(['class' => 'font-mono tabular-nums text-2xl font-bold']),
                        ]),
                    ]),

                // ─────────────────────────────── REPORT WARNING (12/12, conditional)
                Section::make('Upozorenja')
                    ->columnSpan(12)
                    ->compact()
                    ->visible(fn ($record) => \App\Models\UserReport::where('reported_id', $record->id)->where('status', 'pending')->exists())
                    ->schema([
                        TextEntry::make('pending_reports_warning')
                            ->label('')
                            ->state(fn ($record) =>
                                \App\Models\UserReport::where('reported_id', $record->id)->where('status', 'pending')->count() .
                                ' otvorenih prijava protiv ovog korisnika.')
                            ->badge()
                            ->color('danger')
                            ->icon('heroicon-m-exclamation-triangle')
                            ->extraAttributes(['class' => 'text-sm']),
                    ]),

                // ─────────────────────────────── TABS (12/12)
                Tabs::make('Aktivnost')
                    ->columnSpan(12)
                    ->tabs([

                        Tab::make('Oglasi')
                            ->icon('heroicon-m-squares-plus')
                            ->badge(fn ($record) => $record->products()->count())
                            ->schema([
                                RepeatableEntry::make('products')
                                    ->label('')
                                    ->state(fn ($record) => $record->products()->latest()->limit(10)->get())
                                    ->schema([
                                        Grid::make(5)->schema([
                                            TextEntry::make('title')->label('Naslov')->limit(40)->weight('semibold'),
                                            TextEntry::make('brand.name')->label('Brend')->placeholder('—')->color('gray'),
                                            TextEntry::make('price')->label('Cijena')->money('BAM'),
                                            TextEntry::make('status')
                                                ->label('Status')
                                                ->badge()
                                                ->color(fn ($state) => match ($state) {
                                                    'active'         => 'success',
                                                    'pending_review' => 'primary',
                                                    'sold'           => 'gray',
                                                    default          => 'warning',
                                                }),
                                            TextEntry::make('created_at')->label('Datum')->date('d.m.Y.'),
                                        ]),
                                    ])
                                    ->placeholder('Korisnik nema oglasa.'),
                            ]),

                        Tab::make('Kupovine')
                            ->icon('heroicon-m-shopping-bag')
                            ->badge(fn ($record) => $record->ordersAsBuyer()->count())
                            ->schema([
                                RepeatableEntry::make('ordersAsBuyer')
                                    ->label('')
                                    ->state(fn ($record) => $record->ordersAsBuyer()->latest()->limit(10)->get())
                                    ->schema([
                                        Grid::make(5)->schema([
                                            TextEntry::make('order_number')->label('ID')->prefix('#')->extraAttributes(['class' => 'font-mono']),
                                            TextEntry::make('seller.username')->label('Prodavac')->prefix('@')->extraAttributes(['class' => 'font-mono text-xs']),
                                            TextEntry::make('product.title')->label('Oglas')->limit(28),
                                            TextEntry::make('total')->label('Iznos')->money('BAM'),
                                            TextEntry::make('status')
                                                ->label('Status')
                                                ->badge()
                                                ->color(fn ($state) => match ($state) {
                                                    'completed','delivered' => 'success',
                                                    'declined'              => 'danger',
                                                    'shipped'               => 'primary',
                                                    default                 => 'gray',
                                                }),
                                        ]),
                                    ])
                                    ->placeholder('Nema kupovina.'),
                            ]),

                        Tab::make('Prodaje')
                            ->icon('heroicon-m-banknotes')
                            ->badge(fn ($record) => $record->ordersAsSeller()->count())
                            ->schema([
                                RepeatableEntry::make('ordersAsSeller')
                                    ->label('')
                                    ->state(fn ($record) => $record->ordersAsSeller()->latest()->limit(10)->get())
                                    ->schema([
                                        Grid::make(5)->schema([
                                            TextEntry::make('order_number')->label('ID')->prefix('#')->extraAttributes(['class' => 'font-mono']),
                                            TextEntry::make('buyer.username')->label('Kupac')->prefix('@')->extraAttributes(['class' => 'font-mono text-xs']),
                                            TextEntry::make('product.title')->label('Oglas')->limit(28),
                                            TextEntry::make('total')->label('Iznos')->money('BAM'),
                                            TextEntry::make('status')
                                                ->label('Status')
                                                ->badge()
                                                ->color(fn ($state) => match ($state) {
                                                    'completed','delivered' => 'success',
                                                    'declined'              => 'danger',
                                                    'shipped'               => 'primary',
                                                    default                 => 'gray',
                                                }),
                                        ]),
                                    ])
                                    ->placeholder('Nema prodaja.'),
                            ]),

                        Tab::make('Prijave protiv')
                            ->icon('heroicon-m-flag')
                            ->badge(fn ($record) => \App\Models\UserReport::where('reported_id', $record->id)->count())
                            ->badgeColor(fn ($record) => \App\Models\UserReport::where('reported_id', $record->id)->where('status', 'pending')->exists() ? 'danger' : 'gray')
                            ->schema([
                                RepeatableEntry::make('reportsReceived')
                                    ->label('')
                                    ->state(fn ($record) => \App\Models\UserReport::where('reported_id', $record->id)->latest()->limit(10)->get())
                                    ->schema([
                                        Grid::make(4)->schema([
                                            TextEntry::make('reporter.username')->label('Prijavio')->prefix('@')->extraAttributes(['class' => 'font-mono text-xs']),
                                            TextEntry::make('reason')->label('Razlog')->badge()->color('warning'),
                                            TextEntry::make('status')
                                                ->label('Status')
                                                ->badge()
                                                ->color(fn ($state) => match ($state) {
                                                    'pending'    => 'warning',
                                                    'restricted','banned' => 'danger',
                                                    default      => 'gray',
                                                }),
                                            TextEntry::make('created_at')->label('Datum')->date('d.m.Y.'),
                                        ]),
                                        TextEntry::make('description')->label('Opis')->prose()->placeholder('—'),
                                    ])
                                    ->placeholder('Nema prijava protiv ovog korisnika.'),
                            ]),

                        Tab::make('Blokirao')
                            ->icon('heroicon-m-no-symbol')
                            ->badge(fn ($record) => $record->blocksMade()->count())
                            ->schema([
                                RepeatableEntry::make('blocksMade')
                                    ->label('')
                                    ->state(fn ($record) => $record->blocksMade()->latest()->limit(20)->get())
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextEntry::make('blocked.username')->label('Blokiran')->prefix('@')->extraAttributes(['class' => 'font-mono text-xs']),
                                            TextEntry::make('reason')->label('Razlog')->placeholder('—')->color('gray'),
                                            TextEntry::make('created_at')->label('Datum')->dateTime('d.m.Y. H:i'),
                                        ]),
                                    ])
                                    ->placeholder('Nije nikoga blokirao.'),
                            ]),

                        Tab::make('Uređaji')
                            ->icon('heroicon-m-device-phone-mobile')
                            ->badge(fn ($record) => $record->pushTokens()->count())
                            ->schema([
                                RepeatableEntry::make('pushTokens')
                                    ->label('')
                                    ->state(fn ($record) => $record->pushTokens()->latest()->get())
                                    ->schema([
                                        Grid::make(4)->schema([
                                            TextEntry::make('platform')->label('Platforma')->badge()->color('gray'),
                                            TextEntry::make('device_name')->label('Uređaj')->placeholder('—'),
                                            TextEntry::make('token')->label('Token')->limit(24)->extraAttributes(['class' => 'font-mono text-xs']),
                                            TextEntry::make('updated_at')->label('Aktivnost')->since(),
                                        ]),
                                    ])
                                    ->placeholder('Nema registriranih uređaja.'),
                            ]),

                        Tab::make('Audit')
                            ->icon('heroicon-m-clipboard-document-list')
                            ->schema([
                                RepeatableEntry::make('audit')
                                    ->label('')
                                    ->state(fn ($record) => \Spatie\Activitylog\Models\Activity::query()
                                        ->where('causer_id', $record->id)
                                        ->orWhere(function ($q) use ($record) {
                                            $q->where('subject_type', \App\Models\User::class)
                                              ->where('subject_id', $record->id);
                                        })
                                        ->latest()
                                        ->limit(20)
                                        ->get())
                                    ->schema([
                                        Grid::make(4)->schema([
                                            TextEntry::make('created_at')->label('Vrijeme')->dateTime('d.m.Y. H:i')->extraAttributes(['class' => 'font-mono text-xs']),
                                            TextEntry::make('event')->label('Akcija')->badge()
                                                ->color(fn ($state) => match ($state) {
                                                    'created' => 'success',
                                                    'updated' => 'warning',
                                                    'deleted' => 'danger',
                                                    default   => 'gray',
                                                }),
                                            TextEntry::make('subject_type')->label('Tip')->formatStateUsing(fn ($state) => class_basename($state ?? '—'))->color('gray'),
                                            TextEntry::make('description')->label('Opis')->limit(40),
                                        ]),
                                    ])
                                    ->placeholder('Nema zabilježene aktivnosti.'),
                            ]),
                    ]),

                // ─────────────────────────────── ACCOUNT INFO (12/12)
                Section::make('Account')
                    ->columnSpan(12)
                    ->compact()
                    ->collapsed()
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('id')->label('User ID')->prefix('#')->extraAttributes(['class' => 'font-mono']),
                            TextEntry::make('created_at')->label('Pridružio se')->dateTime('d.m.Y. H:i'),
                            TextEntry::make('last_active_at')->label('Posljednja aktivnost')->since()->placeholder('—'),
                            TextEntry::make('email_verified_at')->label('Email verifikovan')->dateTime('d.m.Y. H:i')->placeholder('Ne'),
                            TextEntry::make('phone')->label('Telefon')->placeholder('—')->extraAttributes(['class' => 'font-mono']),
                            TextEntry::make('phone_verified_at')->label('Telefon verifikovan')->dateTime('d.m.Y. H:i')->placeholder('Ne'),
                            TextEntry::make('google_id')->label('Google')->formatStateUsing(fn ($state) => $state ? 'Da' : 'Ne')->color(fn ($state) => $state ? 'success' : 'gray'),
                            TextEntry::make('apple_id')->label('Apple')->formatStateUsing(fn ($state) => $state ? 'Da' : 'Ne')->color(fn ($state) => $state ? 'success' : 'gray'),
                        ]),
                    ]),
            ]);
    }
}
