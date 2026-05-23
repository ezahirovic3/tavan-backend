<?php

namespace App\Filament\Resources\ShareViews;

use App\Filament\Resources\ShareViews\Pages\ListShareViews;
use App\Filament\Resources\ShareViews\Tables\ShareViewsTable;
use App\Models\ShareView;
use App\Models\ShareViewSummary;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ShareViewResource extends Resource
{
    protected static ?string $model = ShareViewSummary::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-top-right-on-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Analitika';

    protected static ?string $navigationLabel = 'Dijeljenja';

    protected static ?string $modelLabel = 'dijeljenje';

    protected static ?string $pluralModelLabel = 'dijeljenja';

    protected static ?int $navigationSort = 51;

    public static function getEloquentQuery(): Builder
    {
        return ShareViewSummary::query()
            ->select([
                'entity_type',
                'entity_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(platform = "ios") as ios_count'),
                DB::raw('SUM(platform = "android") as android_count'),
                DB::raw('SUM(platform = "desktop") as desktop_count'),
                DB::raw('SUM(outcome = "app_opened") as opened_count'),
                DB::raw('SUM(outcome = "store_redirect") as redirect_count'),
                DB::raw('MAX(created_at) as last_seen'),
            ])
            ->groupBy('entity_type', 'entity_id');
    }

    public static function table(Table $table): Table
    {
        return ShareViewsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShareViews::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
