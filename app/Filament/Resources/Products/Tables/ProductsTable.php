<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Brand;
use App\Services\ConversationService;
use App\Services\PushNotificationService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('')
                    ->square()
                    ->size(48)
                    ->getStateUsing(fn ($record) => $record->images->first()?->url),

                TextColumn::make('title')
                    ->label('Naslov')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->limit(48)
                    ->wrap(),

                TextColumn::make('seller.username')
                    ->label('Prodavac')
                    ->prefix('@')
                    ->searchable()
                    ->color(fn ($record) => $record->seller?->deletion_requested_at ? 'danger' : 'gray')
                    ->description(fn ($record) => $record->seller?->deletion_requested_at
                        ? 'Briše se ' . \Carbon\Carbon::parse($record->seller->deletion_requested_at)->addDays(30)->format('d.m.Y.')
                        : null)
                    ->extraAttributes(['class' => 'font-mono text-xs']),

                TextColumn::make('brand.name')
                    ->label('Brend')
                    ->color('gray')
                    ->size('sm'),

                TextColumn::make('price')
                    ->label('Cijena')
                    ->money('BAM')
                    ->sortable()
                    ->alignEnd()
                    ->weight('semibold'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active'         => 'success',
                        'pending_review' => 'primary',
                        'sold'           => 'gray',
                        'draft'          => 'warning',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active'         => 'Aktivan',
                        'pending_review' => 'Na pregledu',
                        'sold'           => 'Prodano',
                        'draft'          => 'Draft',
                        default          => $state,
                    }),

                TextColumn::make('vintage_status')
                    ->label('Vintage')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'approved' => 'warning',
                        'pending'  => 'gray',
                        'rejected' => 'danger',
                        default    => null,
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'approved' => '✦ Vintage',
                        'pending'  => 'Na čekanju',
                        'rejected' => 'Odbijeno',
                        default    => null,
                    })
                    ->placeholder('—'),

                TextColumn::make('view_count')
                    ->label('Pregledi')
                    ->sortable()
                    ->alignEnd()
                    ->color('gray')
                    ->size('sm'),

                TextColumn::make('created_at')
                    ->label('Datum')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->color('gray')
                    ->size('sm'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'pending_review' => 'Na pregledu',
                    'active' => 'Aktivan',
                    'sold' => 'Prodano',
                ])->multiple(),

                SelectFilter::make('category')->options([
                    'tops'        => 'Tops',
                    'bottoms'     => 'Bottoms',
                    'jackets'     => 'Jackets',
                    'dresses'     => 'Dresses',
                    'shoes'       => 'Shoes',
                    'bags'        => 'Bags',
                    'accessories' => 'Accessories',
                    'jewelry'     => 'Jewelry',
                    'activewear'  => 'Activewear',
                    'occasion'    => 'Occasion',
                ])->multiple(),

                SelectFilter::make('brand_id')
                    ->label('Brend')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('vintage_status')
                    ->label('Vintage status')
                    ->options([
                        'pending'  => 'Na čekanju',
                        'approved' => 'Odobreno',
                        'rejected' => 'Odbijeno',
                    ]),

                Filter::make('seller_pending_deletion')
                    ->label('Prodavac na brisanju')
                    ->toggle()
                    ->query(fn (Builder $q, array $data) => $data['isActive']
                        ? $q->whereHas('seller', fn ($q) => $q->whereNotNull('deletion_requested_at'))
                        : $q),

                Filter::make('created_at')
                    ->label('Datum')
                    ->schema([
                        DatePicker::make('from')->label('Od'),
                        DatePicker::make('to')->label('Do'),
                    ])
                    ->query(function (Builder $q, array $data): Builder {
                        return $q
                            ->when($data['from'], fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['to'],   fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('approve')
                    ->label('Odobri')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending_review')
                    ->requiresConfirmation()
                    ->modalHeading('Odobri oglas')
                    ->modalDescription(fn ($record) => $record->seller?->listings_require_review
                        ? 'Oglas postaje aktivan. Prodavac dobija obavijest da su budući oglasi odobreni i idu direktno online.'
                        : 'Oglas postaje aktivan i vidljiv u aplikaciji.')
                    ->action(function ($record) {
                        $record->update(['status' => 'active']);

                        $seller = $record->seller;

                        if ($seller && $seller->listings_require_review) {
                            $seller->update(['listings_require_review' => false]);

                            $conversations = app(ConversationService::class);
                            $push = app(PushNotificationService::class);

                            $conversation = $conversations->findOrCreateSupportConversation($seller->id);

                            $conversations->sendSupportReply(
                                $conversation,
                                auth()->user(),
                                "Tvoji oglasi su pregledani i odobreni! 🎉\n\nOd sada svi tvoji novi oglasi idu direktno online — nema više čekanja na pregled.\n\nNapomena: Ako primimo prijave vezane za tvoj profil ili oglase, pregled može biti ponovo uključen. Hvala na razumijevanju i dobrodošao/la u Tavan zajednicu! 🩷",
                            );

                            $push->sendToUser(
                                $seller->id,
                                'Tavan Podrška',
                                'Tvoji oglasi su odobreni! Od sada objavljuješ direktno online 🎉',
                                ['type' => 'support_message', 'conversationId' => $conversation->id],
                            );

                            Notification::make()->success()->title('Oglas odobren — prodavac odobren, poruka poslana')->send();
                            return;
                        }

                        Notification::make()->success()->title('Oglas odobren')->send();
                    }),

                Action::make('reject')
                    ->label('Odbaci')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'pending_review')
                    ->schema([
                        Textarea::make('reason')
                            ->label('Razlog odbijanja')
                            ->required()
                            ->rows(3)
                            ->helperText('Šalje se prodavcu kao poruka u support konverzaciji.'),
                    ])
                    ->modalHeading('Odbaci oglas')
                    ->action(function (array $data, $record) {
                        $record->update(['status' => 'draft']);

                        $conversations = app(ConversationService::class);
                        $push = app(PushNotificationService::class);

                        $conversation = $conversations->findOrCreateSupportConversation($record->seller_id);
                        $messageBody = "Tvoj oglas \"{$record->title}\" je odbijen.\n\nRazlog: {$data['reason']}";
                        $conversations->sendSupportReply($conversation, auth()->user(), $messageBody);

                        $push->sendToUser(
                            $record->seller_id,
                            'Oglas odbijen',
                            "Tvoj oglas \"{$record->title}\" je odbijen. Otvori poruke za detalje.",
                            ['type' => 'support_message', 'conversationId' => $conversation->id],
                        );

                        Notification::make()->success()->title('Oglas odbačen, poruka poslana prodavcu')->send();
                    }),

                ActionGroup::make([
                    EditAction::make(),

                    Action::make('changeBrand')
                        ->label('Promijeni brend')
                        ->icon('heroicon-m-tag')
                        ->color('gray')
                        ->schema([
                            Select::make('brand_id')
                                ->label('Brend')
                                ->relationship('brand', 'name')
                                ->searchable()
                                ->preload()
                                ->placeholder('— Bez brenda —'),
                        ])
                        ->fillForm(fn ($record) => ['brand_id' => $record->brand_id])
                        ->modalHeading('Promijeni brend')
                        ->modalSubmitActionLabel('Sačuvaj')
                        ->action(fn (array $data, $record) => $record->update(['brand_id' => $data['brand_id'] ?: null])),

                    Action::make('forceStatus')
                        ->label('Promijeni status (force)')
                        ->icon('heroicon-m-wrench-screwdriver')
                        ->color('warning')
                        ->schema([
                            Select::make('status')
                                ->label('Novi status')
                                ->options([
                                    'draft'          => 'Draft',
                                    'pending_review' => 'Pending review',
                                    'active'         => 'Active',
                                    'reserved'       => 'Reserved',
                                    'sold'           => 'Sold',
                                ])
                                ->required(),
                        ])
                        ->modalHeading('Force status change')
                        ->modalDescription('Koristi samo za state recovery (bug, zaglavljen oglas).')
                        ->action(fn (array $data, $record) => $record->update(['status' => $data['status']])),
                ])->dropdown(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => auth()->user()->isSuperAdmin()),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
