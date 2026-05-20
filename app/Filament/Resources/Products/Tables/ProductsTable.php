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
                    ->color('gray')
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
                    'jakne' => 'Jakne',
                    'majice' => 'Majice',
                    'pantalone' => 'Pantalone',
                    'haljine' => 'Haljine',
                    'cipele' => 'Cipele',
                    'torbe' => 'Torbe',
                    'aksesoari' => 'Aksesoari',
                ])->multiple(),

                SelectFilter::make('brand_id')
                    ->label('Brend')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),

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
                    ->modalDescription('Oglas postaje aktivan i vidljiv u aplikaciji.')
                    ->action(function ($record) {
                        $record->update(['status' => 'active']);
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
                        $record->update([
                            'status' => 'draft',
                            'rejection_reason' => $data['reason'],
                            'rejected_at' => now(),
                            'rejected_by' => auth()->id(),
                        ]);

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

                    Action::make('forceStatus')
                        ->label('Promijeni status (force)')
                        ->icon('heroicon-m-wrench-screwdriver')
                        ->color('warning')
                        ->schema([
                            Select::make('status')
                                ->label('Novi status')
                                ->options([
                                    'draft' => 'Draft',
                                    'pending_review' => 'Pending review',
                                    'active' => 'Active',
                                    'sold' => 'Sold',
                                ])
                                ->required(),
                            Textarea::make('admin_note')->label('Napomena')->rows(2),
                        ])
                        ->modalHeading('Force status change')
                        ->modalDescription('Koristi samo za state recovery (bug, zaglavljen oglas).')
                        ->action(fn (array $data, $record) => $record->update([
                            'status' => $data['status'],
                            'admin_note' => $data['admin_note'] ?? null,
                        ])),
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
