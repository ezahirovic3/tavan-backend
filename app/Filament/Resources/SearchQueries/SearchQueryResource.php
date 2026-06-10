<?php

namespace App\Filament\Resources\SearchQueries;

use App\Filament\Resources\SearchQueries\Pages\ListSearchQueries;
use App\Filament\Resources\SearchQueries\Tables\SearchQueriesTable;
use App\Models\SearchQuery;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class SearchQueryResource extends Resource
{
    protected static ?string $model = SearchQuery::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static string|\UnitEnum|null $navigationGroup = 'Analitika';

    protected static ?string $navigationLabel = 'Pretrage';

    protected static ?string $modelLabel = 'pretraga';

    protected static ?string $pluralModelLabel = 'pretrage';

    protected static ?int $navigationSort = 52;

    public static function table(Table $table): Table
    {
        return SearchQueriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSearchQueries::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
