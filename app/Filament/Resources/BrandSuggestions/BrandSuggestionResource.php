<?php

namespace App\Filament\Resources\BrandSuggestions;

use App\Filament\Resources\BrandSuggestions\Pages\ListBrandSuggestions;
use App\Filament\Resources\BrandSuggestions\Pages\ViewBrandSuggestion;
use App\Models\Brand;
use App\Models\BrandSuggestion;
use App\Services\ConversationService;
use App\Services\PushNotificationService;
use App\Services\UserNotificationService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BrandSuggestionResource extends Resource
{
    protected static ?string $model = BrandSuggestion::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-light-bulb';

    protected static string|\UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?string $navigationLabel = 'Prijedlozi brendova';

    protected static ?string $modelLabel = 'prijedlog';

    protected static ?string $pluralModelLabel = 'prijedlozi brendova';

    protected static ?int $navigationSort = 21;

    public static function getNavigationBadge(): ?string
    {
        $n = static::getModel()::where('status', 'pending')->count();
        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary'; // pink — pulls attention
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Prijedlog')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label('Predloženi naziv')->weight('bold'),
                        TextEntry::make('user.username')->label('Korisnik')->prefix('@'),
                        TextEntry::make('created_at')->label('Datum')->dateTime('d.m.Y. H:i'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'pending'  => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default    => 'gray',
                            }),
                        TextEntry::make('note')->label('Napomena')->columnSpanFull()->placeholder('—'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Predloženi naziv')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('user.username')
                    ->label('Korisnik')
                    ->prefix('@')
                    ->searchable()
                    ->color('gray')
                    ->extraAttributes(['class' => 'font-mono text-xs']),

                TextColumn::make('created_at')
                    ->label('Datum')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->color('gray')
                    ->size('sm'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => Str::ucfirst($state)),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending'  => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])->default('pending'),
            ])
            ->recordActions([
                ViewAction::make(),
                static::approveAction(),
                static::rejectAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => auth()->user()->isSuperAdmin()),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Approve action — shared by the table row and the view page header.
     * Registers the brand in the catalogue in the same step (toggle on by
     * default) so there's no detour to the Brands screen.
     */
    public static function approveAction(): Action
    {
        return Action::make('approve')
            ->label('Odobri')
            ->icon('heroicon-m-check')
            ->color('success')
            ->visible(fn ($record) => $record->status === 'pending')
            ->fillForm(fn ($record) => [
                'register_brand' => true,
                'brand_name'     => $record->name,
            ])
            ->schema([
                Toggle::make('register_brand')
                    ->label('Dodaj brend u katalog')
                    ->helperText('Kreira brend odmah — bez odlaska na ekran Brendovi.')
                    ->live(),

                TextInput::make('brand_name')
                    ->label('Naziv brenda')
                    ->helperText('Ispravi ako treba — ovaj naziv ide u katalog.')
                    ->maxLength(120)
                    ->required(fn (Get $get) => (bool) $get('register_brand'))
                    ->visible(fn (Get $get) => (bool) $get('register_brand')),

                Textarea::make('note')
                    ->label('Poruka korisniku (opcionalno)')
                    ->placeholder('Npr. Brend je dodan u katalog i uskoro će biti dostupan.')
                    ->rows(3)
                    ->maxLength(500),
            ])
            ->modalHeading('Odobri prijedlog')
            ->modalSubmitActionLabel('Odobri')
            ->action(fn (array $data, $record) => static::handleApprove($data, $record));
    }

    /**
     * Reject action — shared by the table row and the view page header.
     */
    public static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('Odbaci')
            ->icon('heroicon-m-x-mark')
            ->color('danger')
            ->visible(fn ($record) => $record->status === 'pending')
            ->schema([
                Textarea::make('note')
                    ->label('Razlog odbijanja (opcionalno)')
                    ->placeholder('Npr. Brend već postoji u katalogu pod drugim nazivom.')
                    ->rows(3)
                    ->maxLength(500),
            ])
            ->modalHeading('Odbaci prijedlog')
            ->modalSubmitActionLabel('Odbaci')
            ->action(fn (array $data, $record) => static::handleReject($data, $record));
    }

    protected static function handleApprove(array $data, BrandSuggestion $record): void
    {
        $brandNotice = '';

        if (! empty($data['register_brand'])) {
            $name     = trim($data['brand_name'] ?? '') ?: $record->name;
            $existing = Brand::findByNormalizedName($name, activeOnly: false);

            if ($existing) {
                $brandNotice = ' (brend "' . $existing->name . '" već postoji)';
            } else {
                Brand::create([
                    'name'      => $name,
                    'slug'      => static::uniqueBrandSlug($name),
                    'is_active' => true,
                    'is_other'  => false,
                    // Append to the end — a non-null value that matches no
                    // existing row, so Brand's saving hook doesn't reshuffle
                    // the whole catalogue.
                    'sort_order' => (Brand::max('sort_order') ?? 0) + 1,
                ]);
                $brandNotice = ' — brend dodan u katalog';
            }
        }

        $record->update([
            'status'      => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $pushTitle   = 'Prijedlog brenda odobren ✓';
        $pushMessage = 'Tvoj prijedlog brenda "' . $record->name . '" je odobren.';
        app(PushNotificationService::class)->sendToUser(
            $record->user_id,
            $pushTitle,
            $pushMessage,
            ['type' => 'brand_suggestion_approved'],
        );

        app(UserNotificationService::class)->record(
            $record->user,
            'brand_suggestion_approved',
            $pushTitle,
            $pushMessage,
            ['brandSuggestionId' => $record->id],
        );

        $supportMessage = $data['note'] ?: $pushMessage;
        static::postSupportMessage($record->user_id, $supportMessage);

        Notification::make()->success()->title('Prijedlog odobren' . $brandNotice)->send();
    }

    protected static function handleReject(array $data, BrandSuggestion $record): void
    {
        $record->update([
            'status'      => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $pushTitle   = 'Prijedlog brenda odbijen';
        $pushMessage = 'Tvoj prijedlog brenda "' . $record->name . '" nije odobren.';
        app(PushNotificationService::class)->sendToUser(
            $record->user_id,
            $pushTitle,
            $pushMessage,
            ['type' => 'brand_suggestion_rejected'],
        );

        app(UserNotificationService::class)->record(
            $record->user,
            'brand_suggestion_rejected',
            $pushTitle,
            $pushMessage,
            ['brandSuggestionId' => $record->id],
        );

        $supportMessage = $data['note'] ?: $pushMessage;
        static::postSupportMessage($record->user_id, $supportMessage);

        Notification::make()->success()->title('Prijedlog odbačen')->send();
    }

    protected static function uniqueBrandSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'brend';
        $slug = $base;
        $i    = 2;

        while (Brand::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    public static function postSupportMessage(string $userId, string $body): void
    {
        $conversations = app(ConversationService::class);
        $convo = $conversations->findOrCreateSupportConversation($userId);
        $conversations->sendSupportReply($convo, auth()->user(), $body);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBrandSuggestions::route('/'),
            'view'  => ViewBrandSuggestion::route('/{record}'),
        ];
    }
}
