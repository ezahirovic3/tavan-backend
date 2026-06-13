<?php

namespace App\Filament\Resources\BrandSuggestions;

use App\Filament\Resources\BrandSuggestions\Pages\ListBrandSuggestions;
use App\Filament\Resources\BrandSuggestions\Pages\ViewBrandSuggestion;
use App\Models\BrandSuggestion;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\PushNotificationService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
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

                Action::make('approve')
                    ->label('Odobri')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->schema([
                        Textarea::make('note')
                            ->label('Poruka korisniku (opcionalno)')
                            ->placeholder('Npr. Brend je dodan u katalog i uskoro će biti dostupan.')
                            ->rows(3)
                            ->maxLength(500),
                    ])
                    ->modalHeading('Odobri prijedlog')
                    ->modalSubmitActionLabel('Odobri')
                    ->action(function (array $data, $record) {
                        $record->update([
                            'status'      => 'approved',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        $pushMessage = 'Tvoj prijedlog brenda "' . $record->name . '" je odobren.';
                        app(PushNotificationService::class)->sendToUser(
                            $record->user_id,
                            'Prijedlog brenda odobren ✓',
                            $pushMessage,
                            ['type' => 'brand_suggestion_approved'],
                        );

                        $supportMessage = $data['note'] ?: $pushMessage;
                        static::postSupportMessage($record->user_id, $supportMessage);

                        Notification::make()->success()->title('Prijedlog odobren')->send();
                    }),

                Action::make('reject')
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
                    ->action(function (array $data, $record) {
                        $record->update([
                            'status'      => 'rejected',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        $pushMessage = 'Tvoj prijedlog brenda "' . $record->name . '" nije odobren.';
                        app(PushNotificationService::class)->sendToUser(
                            $record->user_id,
                            'Prijedlog brenda odbijen',
                            $pushMessage,
                            ['type' => 'brand_suggestion_rejected'],
                        );

                        $supportMessage = $data['note'] ?: $pushMessage;
                        static::postSupportMessage($record->user_id, $supportMessage);

                        Notification::make()->success()->title('Prijedlog odbačen')->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => auth()->user()->isSuperAdmin()),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function postSupportMessage(string $userId, string $body): void
    {
        $convo = Conversation::firstOrCreate(
            [
                'participant_one_id' => $userId,
                'participant_two_id' => config('tavan.system_user_id'),
            ],
            [
                'allow_replies'   => true,
                'status'          => 'open',
                'type'            => 'admin_support',
                'last_message_at' => now(),
            ]
        );

        if (! $convo->wasRecentlyCreated && $convo->status === 'closed') {
            $convo->update(['status' => 'open', 'allow_replies' => true]);
        }

        Message::create([
            'conversation_id' => $convo->id,
            'sender_id'       => auth()->id(),
            'body'            => $body,
        ]);

        $convo->update(['last_message_at' => now()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBrandSuggestions::route('/'),
            'view'  => ViewBrandSuggestion::route('/{record}'),
        ];
    }
}
