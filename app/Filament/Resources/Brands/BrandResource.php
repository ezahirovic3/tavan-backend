<?php

namespace App\Filament\Resources\Brands;

use App\Filament\Resources\Brands\Pages\ManageBrands;
use App\Models\Brand;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?string $navigationLabel = 'Brendovi';

    protected static ?string $modelLabel = 'brend';

    protected static ?string $pluralModelLabel = 'brendovi';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    TextInput::make('name')
                        ->label('Naziv')
                        ->required()
                        ->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state ?? ''))),

                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(140)
                        ->unique(ignoreRecord: true)
                        ->extraInputAttributes(['class' => 'font-mono']),
                ]),

                FileUpload::make('logo_url')
                    ->label('Logo')
                    ->image()
                    ->disk('r2')
                    ->directory('brands/logos')
                    ->imageEditor(),

                Grid::make(2)->schema([
                    Toggle::make('is_active')
                        ->label('Aktivan')
                        ->default(true)
                        ->onColor('success'),

                    TextInput::make('sort_order')
                        ->label('Redoslijed')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),
                ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->disk('r2')
                    ->square()
                    ->size(40)
                    ->extraImgAttributes(['style' => 'object-fit: contain']),

                TextColumn::make('name')
                    ->label('Naziv')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->color('gray')
                    ->extraAttributes(['class' => 'font-mono text-xs'])
                    ->copyable(),

                TextColumn::make('products_count')
                    ->label('Oglasi')
                    ->counts('products')
                    ->numeric()
                    ->color('gray'),

                ToggleColumn::make('is_active')
                    ->label('Aktivan')
                    ->onColor('success'),

                TextInputColumn::make('sort_order')
                    ->label('Sort')
                    ->type('number')
                    ->extraAttributes(['class' => 'w-16']),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Status')
                    ->trueLabel('Samo aktivni')
                    ->falseLabel('Samo neaktivni')
                    ->placeholder('Svi'),
            ])
            ->recordActions([
                EditAction::make()
                    ->slideOver()
                    ->using(function (array $data, Model $record): void {
                        $newOrder = (int) $data['sort_order'];
                        $oldOrder = (int) $record->sort_order;

                        if ($newOrder !== $oldOrder) {
                            Brand::where('id', '!=', $record->getKey())
                                ->where('sort_order', '>=', $newOrder)
                                ->increment('sort_order');
                        }

                        $record->update($data);
                    }),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => auth()->user()->isSuperAdmin()),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBrands::route('/'),
        ];
    }
}
