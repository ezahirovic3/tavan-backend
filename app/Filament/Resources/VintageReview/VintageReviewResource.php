<?php

namespace App\Filament\Resources\VintageReview;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\VintageReview\Pages\ListVintageReviews;
use App\Models\Product;
use App\Services\ConversationService;
use App\Services\PushNotificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VintageReviewResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|\UnitEnum|null $navigationGroup = 'Moderacija';

    protected static ?string $navigationLabel = 'Vintage pregled';

    protected static ?string $modelLabel = 'vintage zahtjev';

    protected static ?string $pluralModelLabel = 'vintage zahtjevi';

    protected static ?int $navigationSort = 45;

    public const ERA_LABELS = [
        '50s' => '1950s',
        '60s' => '1960s',
        '70s' => '1970s',
        '80s' => '1980s',
        '90s' => '1990s',
        'y2k' => 'Y2K (2000s)',
    ];

    public static function getNavigationBadge(): ?string
    {
        $n = static::getModel()::where('vintage_status', 'pending')->count();
        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereIn('vintage_status', ['pending', 'approved', 'rejected']))
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('')
                    ->square()
                    ->size(56)
                    ->getStateUsing(fn ($record) => $record->images->first()?->url),

                TextColumn::make('title')
                    ->label('Oglas')
                    ->searchable()
                    ->weight('semibold')
                    ->limit(48)
                    ->description(fn ($record) => '@' . $record->seller?->username),

                TextColumn::make('vintage_era')
                    ->label('Era')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => self::ERA_LABELS[$state] ?? $state),

                TextColumn::make('vintage_notes')
                    ->label('Opis prodavca')
                    ->limit(80)
                    ->wrap()
                    ->color('gray'),

                TextColumn::make('vintage_provenance')
                    ->label('Porijeklo')
                    ->limit(60)
                    ->color('gray')
                    ->placeholder('—'),

                TextColumn::make('vintage_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending'  => 'Na čekanju',
                        'approved' => 'Odobreno',
                        'rejected' => 'Odbijeno',
                        default    => $state,
                    }),

                TextColumn::make('created_at')
                    ->label('Datum')
                    ->date('d.m.Y.')
                    ->color('gray')
                    ->size('sm')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('vintage_status')
                    ->label('Status')
                    ->options([
                        'pending'  => 'Na čekanju',
                        'approved' => 'Odobreno',
                        'rejected' => 'Odbijeno',
                    ])
                    ->default('pending'),

                SelectFilter::make('vintage_era')
                    ->label('Era')
                    ->options(self::ERA_LABELS),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Odobri')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->vintage_status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Odobri vintage badge')
                    ->modalDescription('Badge postaje vidljiv na oglasu u aplikaciji.')
                    ->action(function ($record) {
                        $record->update([
                            'vintage_status'     => 'approved',
                            'vintage_reviewed_by'=> auth()->id(),
                            'vintage_reviewed_at'=> now(),
                            'vintage_reject_reason' => null,
                        ]);

                        app(PushNotificationService::class)->sendToUser(
                            $record->seller_id,
                            'Vintage badge odobren!',
                            "Tvoj oglas \"{$record->title}\" je dobio Vintage badge.",
                            ['type' => 'vintage_approved', 'productId' => $record->id],
                        );

                        Notification::make()->success()->title('Vintage badge odobren')->send();
                    }),

                Action::make('reject')
                    ->label('Odbij')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => $record->vintage_status === 'pending')
                    ->schema([
                        Textarea::make('reason')
                            ->label('Razlog odbijanja')
                            ->required()
                            ->rows(3)
                            ->helperText('Šalje se prodavcu kao poruka u support konverzaciji.'),
                    ])
                    ->modalHeading('Odbij vintage zahtjev')
                    ->action(function (array $data, $record) {
                        $record->update([
                            'vintage_status'        => 'rejected',
                            'vintage_reject_reason' => $data['reason'],
                            'vintage_reviewed_by'   => auth()->id(),
                            'vintage_reviewed_at'   => now(),
                        ]);

                        $conversations = app(ConversationService::class);
                        $push = app(PushNotificationService::class);

                        $conversation = $conversations->findOrCreateSupportConversation($record->seller_id);
                        $messageBody  = "Zahtjev za Vintage badge za oglas \"{$record->title}\" je odbijen.\n\nRazlog: {$data['reason']}";
                        $conversations->sendSupportReply($conversation, auth()->user(), $messageBody);

                        $push->sendToUser(
                            $record->seller_id,
                            'Vintage badge odbijen',
                            "Zahtjev za Vintage badge za \"{$record->title}\" je odbijen. Otvori poruke za detalje.",
                            ['type' => 'support_message', 'conversationId' => $conversation->id],
                        );

                        Notification::make()->success()->title('Zahtjev odbijen, poruka poslana prodavcu')->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn ($record) => ProductResource::getUrl('view', ['record' => $record]));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVintageReviews::route('/'),
        ];
    }
}
