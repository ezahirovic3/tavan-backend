<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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

                Section::make('Vintage')
                    ->columnSpan(12)
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('vintage_status')
                                ->label('Vintage status')
                                ->options([
                                    'pending'  => 'Na čekanju',
                                    'approved' => 'Odobreno',
                                    'rejected' => 'Odbijeno',
                                ])
                                ->placeholder('— Nije vintage —')
                                ->native(false)
                                ->helperText('Postavi na "Odobreno" da odmah dobiješ badge bez review procesa.'),

                            Select::make('vintage_era')
                                ->label('Era')
                                ->options([
                                    '50s' => '1950s',
                                    '60s' => '1960s',
                                    '70s' => '1970s',
                                    '80s' => '1980s',
                                    '90s' => '1990s',
                                    'y2k' => 'Y2K (2000s)',
                                ])
                                ->placeholder('—')
                                ->native(false),

                            TextInput::make('vintage_reject_reason')
                                ->label('Razlog odbijanja')
                                ->maxLength(500)
                                ->placeholder('Ostavi prazno ako nema odbijanja'),
                        ]),

                        Textarea::make('vintage_notes')
                            ->label('Opis (od prodavca)')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        TextInput::make('vintage_provenance')
                            ->label('Porijeklo (od prodavca)')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),

                Section::make('Slike')
                    ->columnSpan(12)
                    ->schema([
                        FileUpload::make('images')
                            ->label('')
                            ->multiple()
                            ->image()
                            ->disk('r2')
                            ->directory('products/admin')
                            ->reorderable()
                            ->panelLayout('grid')
                            ->deletable()
                            ->downloadable()
                            ->maxFiles(10),
                    ]),
            ]);
    }
}
