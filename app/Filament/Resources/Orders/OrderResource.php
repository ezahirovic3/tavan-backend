<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\Schemas\OrderInfolist;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
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

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|\UnitEnum|null $navigationGroup = 'Trgovina';

    protected static ?string $navigationLabel = 'Narudžbe';

    protected static ?string $modelLabel = 'narudžba';

    protected static ?string $pluralModelLabel = 'narudžbe';

    protected static ?int $navigationSort = 31;

    protected static ?string $recordTitleAttribute = 'order_number';

    public const STATUSES = [
        'pending'   => 'Pending',
        'accepted'  => 'Accepted',
        'shipped'   => 'Shipped',
        'delivered' => 'Delivered',
        'completed' => 'Completed',
        'declined'  => 'Declined',
    ];

    public static function statusColor(string $status): string
    {
        return match ($status) {
            'completed', 'delivered' => 'success',
            'declined'               => 'danger',
            'shipped'                => 'primary',
            'accepted'               => 'info',
            'pending'                => 'warning',
            default                  => 'gray',
        };
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('ID')
                    ->extraAttributes(['class' => 'font-mono text-xs uppercase'])
                    ->searchable()
                    ->copyable(),

                TextColumn::make('buyer.username')
                    ->label('Kupac')
                    ->prefix('@')
                    ->color('gray')
                    ->extraAttributes(['class' => 'font-mono text-xs'])
                    ->searchable(),

                TextColumn::make('seller.username')
                    ->label('Prodavac')
                    ->prefix('@')
                    ->color('gray')
                    ->extraAttributes(['class' => 'font-mono text-xs'])
                    ->searchable(),

                TextColumn::make('product.title')
                    ->label('Oglas')
                    ->limit(40)
                    ->searchable()
                    ->wrap(),

                TextColumn::make('total')
                    ->label('Iznos')
                    ->money('BAM')
                    ->alignEnd()
                    ->weight('semibold')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => static::statusColor($state))
                    ->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state),

                TextColumn::make('created_at')
                    ->label('Datum')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->color('gray')
                    ->size('sm'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(self::STATUSES)
                    ->multiple(),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('changeStatus')
                    ->label('Promijeni status')
                    ->icon('heroicon-m-arrow-path')
                    ->color('warning')
                    ->schema([
                        Select::make('status')
                            ->label('Novi status')
                            ->options(self::STATUSES)
                            ->required(),
                        Textarea::make('admin_note')
                            ->label('Razlog (audit log)')
                            ->rows(2)
                            ->required()
                            ->helperText('Bilježi se u activity log.'),
                    ])
                    ->modalHeading('Manuelna promjena statusa narudžbe')
                    ->modalDescription('Samo za state recovery — npr. zaglavljen flow u aplikaciji.')
                    ->action(function (array $data, $record) {
                        $record->update(['status' => $data['status']]);
                        activity()->performedOn($record)->causedBy(auth()->user())
                            ->withProperties(['note' => $data['admin_note']])
                            ->log("Force status → {$data['status']}");
                        Notification::make()->success()->title('Status promijenjen')->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view'  => ViewOrder::route('/{record}'),
        ];
    }
}
