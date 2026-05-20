<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Osnovno')
                    ->columnSpan(8)
                    ->schema([
                        TextInput::make('title')->label('Naslov')->required()->maxLength(180),

                        Textarea::make('description')->label('Opis')->rows(5),

                        Grid::make(3)->schema([
                            Select::make('brand_id')
                                ->label('Brend')
                                ->relationship('brand', 'name')
                                ->searchable()
                                ->preload(),
                            Select::make('category')
                                ->label('Kategorija')
                                ->options([
                                    'tops'       => 'Tops (majice, bluze, košulje)',
                                    'bottoms'    => 'Bottoms (pantalone, suknje)',
                                    'jackets'    => 'Jackets (jakne, kaputi)',
                                    'dresses'    => 'Dresses (haljine)',
                                    'shoes'      => 'Shoes (cipele)',
                                    'bags'       => 'Bags (torbe)',
                                    'accessories'=> 'Accessories (aksesoari)',
                                    'jewelry'    => 'Jewelry (nakit)',
                                    'activewear' => 'Activewear (sportska odjeća)',
                                    'occasion'   => 'Occasion (svečana odjeća)',
                                ])
                                ->searchable(),
                            Select::make('size')
                                ->label('Veličina')
                                ->options(['XS' => 'XS','S' => 'S','M' => 'M','L' => 'L','XL' => 'XL','XXL' => 'XXL']),
                            Select::make('condition')
                                ->label('Stanje')
                                ->options([
                                    'new'       => 'Novo/Nenošeno',
                                    'very_good' => 'Vrlo dobro',
                                    'good'      => 'Dobro',
                                    'worn'      => 'Vidljivo nošeno',
                                ]),
                            TextInput::make('price')
                                ->label('Cijena')
                                ->numeric()
                                ->suffix('KM')
                                ->required(),
                            TextInput::make('color')->label('Boja')->maxLength(40),
                        ]),
                    ]),

                Section::make('Status')
                    ->columnSpan(4)
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'pending_review' => 'Pending review',
                                'active' => 'Active',
                                'sold' => 'Sold',
                            ])
                            ->required()
                            ->native(false),

                        Select::make('seller_id')
                            ->label('Prodavac')
                            ->relationship('seller', 'username')
                            ->searchable()
                            ->preload()
                            ->disabled(),
                    ]),

                Section::make('Slike')
                    ->columnSpan(12)
                    ->schema([
                        FileUpload::make('images')
                            ->label('')
                            ->multiple()
                            ->image()
                            ->disk('r2')
                            ->directory('products')
                            ->reorderable()
                            ->panelLayout('grid')
                            ->maxFiles(8),
                    ]),
            ]);
    }
}
