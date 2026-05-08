<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-bag';
    protected static \UnitEnum|string|null $navigationGroup = 'Sadržaj';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('title')->label('Naziv')->disabled(),
            Textarea::make('description')->label('Opis')->disabled(),
            TextInput::make('price')->label('Cijena')->disabled(),
            Select::make('status')
                ->label('Status')
                ->options([
                    'draft'          => 'Draft',
                    'pending_review' => 'Na pregledu',
                    'active'         => 'Aktivan',
                    'reserved'       => 'Rezervisan',
                    'sold'           => 'Prodan',
                ])
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('images.url')
                    ->label('')
                    ->getStateUsing(fn (Product $record) => $record->images->first()?->url)
                    ->square(),
                TextColumn::make('title')->label('Naziv')->searchable()->limit(40),
                TextColumn::make('seller.username')->label('Prodavač')->searchable(),
                TextColumn::make('price')->label('Cijena')->money('BAM')->sortable(),
                TextColumn::make('condition')->label('Stanje')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'novo'            => 'success',
                        'kao_novo'        => 'info',
                        'odlican'         => 'primary',
                        'dobar'           => 'warning',
                        'zadrzavajuci'    => 'danger',
                        default           => 'gray',
                    }),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'         => 'success',
                        'pending_review' => 'warning',
                        'draft'          => 'gray',
                        'reserved'       => 'info',
                        'sold'           => 'gray',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active'         => 'Aktivan',
                        'pending_review' => 'Na pregledu',
                        'draft'          => 'Draft',
                        'reserved'       => 'Rezervisan',
                        'sold'           => 'Prodan',
                        default          => $state,
                    }),
                TextColumn::make('likes')->label('❤️')->sortable(),
                TextColumn::make('created_at')->label('Objavljeno')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft'          => 'Draft',
                        'pending_review' => 'Na pregledu',
                        'active'         => 'Aktivan',
                        'reserved'       => 'Rezervisan',
                        'sold'           => 'Prodan',
                    ]),
                SelectFilter::make('root_category')
                    ->label('Kategorija')
                    ->options(['women' => 'Žene', 'men' => 'Muškarci']),
            ])
            ->actions([
                ViewAction::make(),

                // Approve: pending_review → active + mark seller as trusted (no future review needed)
                Action::make('approve')
                    ->label('Odobri')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Product $record) => $record->status === 'pending_review')
                    ->action(function (Product $record) {
                        $record->update(['status' => 'active']);
                        // If this was the seller's first-review listing, trust them going forward
                        if ($record->seller->listings_require_review) {
                            $record->seller->update(['listings_require_review' => false]);
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Odobri oglas')
                    ->modalDescription('Oglas će biti objavljen i prodavač će biti označen kao pouzdan (buduće objave idu odmah na aktivan).'),

                // Reject: send back to draft so the seller can edit and resubmit
                Action::make('reject')
                    ->label('Vrati na draft')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Product $record) => $record->status === 'pending_review')
                    ->action(fn (Product $record) => $record->update(['status' => 'draft']))
                    ->requiresConfirmation()
                    ->modalHeading('Odbij oglas')
                    ->modalDescription('Oglas će biti vraćen na draft. Prodavač će morati ponovo podnijeti oglas.'),

                Action::make('deactivate')
                    ->label('Deaktiviraj')
                    ->icon('heroicon-o-eye-slash')
                    ->color('danger')
                    ->visible(fn (Product $record) => $record->status === 'active')
                    ->action(fn (Product $record) => $record->update(['status' => 'draft']))
                    ->requiresConfirmation(),

                Action::make('activate')
                    ->label('Aktiviraj')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->visible(fn (Product $record) => $record->status === 'draft')
                    ->action(fn (Product $record) => $record->update(['status' => 'active']))
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'view'  => Pages\ViewProduct::route('/{record}'),
        ];
    }
}
