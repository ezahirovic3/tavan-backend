<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Schemas\ProductInfolist;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-plus';

    protected static string|\UnitEnum|null $navigationGroup = 'Trgovina';

    protected static ?string $navigationLabel = 'Oglasi';

    protected static ?string $modelLabel = 'oglas';

    protected static ?string $pluralModelLabel = 'oglasi';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationBadge(): ?string
    {
        $n = static::getModel()::where('status', 'pending_review')->count();
        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary'; // pink dot — pending_review needs attention
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'view'  => ViewProduct::route('/{record}'),
            'edit'  => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'seller.username', 'brand.name'];
    }
}
