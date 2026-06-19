<?php

namespace App\Filament\Resources\DesignerReview;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\DesignerReview\Pages\ListDesignerReviews;
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

class DesignerReviewResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static string|\UnitEnum|null $navigationGroup = 'Moderacija';

    protected static ?string $navigationLabel = 'Designer pregled';

    protected static ?string $modelLabel = 'designer zahtjev';

    protected static ?string $pluralModelLabel = 'designer zahtjevi';

    protected static ?int $navigationSort = 46;

    public static function getNavigationBadge(): ?string
    {
        $n = static::getModel()::where('designer_status', 'pending')->count();
        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereIn('designer_status', ['pending', 'approved', 'rejected']))
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

                TextColumn::make('designer_brand')
                    ->label('Brand')
                    ->badge()
                    ->color('info'),

                TextColumn::make('designer_notes')
                    ->label('Opis prodavca')
                    ->limit(80)
                    ->wrap()
                    ->color('gray'),

                TextColumn::make('designer_status')
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
                SelectFilter::make('designer_status')
                    ->label('Status')
                    ->options([
                        'pending'  => 'Na čekanju',
                        'approved' => 'Odobreno',
                        'rejected' => 'Odbijeno',
                    ])
                    ->default('pending'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Odobri')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->designer_status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Odobri designer badge')
                    ->modalDescription('Badge postaje vidljiv na oglasu u aplikaciji.')
                    ->action(function ($record) {
                        $record->update([
                            'designer_status'      => 'approved',
                            'designer_reviewed_by' => auth()->id(),
                            'designer_reviewed_at' => now(),
                            'designer_reject_reason' => null,
                        ]);

                        app(PushNotificationService::class)->sendToUser(
                            $record->seller_id,
                            'Designer badge odobren!',
                            "Tvoj oglas \"{$record->title}\" je dobio Designer badge.",
                            ['type' => 'designer_approved', 'productId' => $record->id],
                        );

                        Notification::make()->success()->title('Designer badge odobren')->send();
                    }),

                Action::make('reject')
                    ->label('Odbij')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => $record->designer_status === 'pending')
                    ->schema([
                        Textarea::make('reason')
                            ->label('Razlog odbijanja')
                            ->required()
                            ->rows(3)
                            ->helperText('Šalje se prodavcu kao poruka u support konverzaciji.'),
                    ])
                    ->modalHeading('Odbij designer zahtjev')
                    ->action(function (array $data, $record) {
                        $record->update([
                            'designer_status'        => 'rejected',
                            'designer_reject_reason' => $data['reason'],
                            'designer_reviewed_by'   => auth()->id(),
                            'designer_reviewed_at'   => now(),
                        ]);

                        $conversations = app(ConversationService::class);
                        $push = app(PushNotificationService::class);

                        $conversation = $conversations->findOrCreateSupportConversation($record->seller_id);
                        $messageBody  = "Zahtjev za Designer badge za oglas \"{$record->title}\" je odbijen.\n\nRazlog: {$data['reason']}";
                        $conversations->sendSupportReply($conversation, auth()->user(), $messageBody);

                        $push->sendToUser(
                            $record->seller_id,
                            'Designer badge odbijen',
                            "Zahtjev za Designer badge za \"{$record->title}\" je odbijen. Otvori poruke za detalje.",
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
            'index' => ListDesignerReviews::route('/'),
        ];
    }
}
