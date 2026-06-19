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
                        TextInput::make('title')->label('Naslov')->maxLength(180),

                        Textarea::make('description')->label('Opis')->rows(5),

                        Grid::make(3)->schema([
                            Select::make('brand_id')
                                ->label('Brend')
                                ->relationship('brand', 'name')
                                ->searchable()
                                ->preload()
                                ->placeholder('— Odaberi brend —'),

                            Select::make('root_category')
                                ->label('Root kategorija')
                                ->options([
                                    'women' => 'Women',
                                    'men'   => 'Men',
                                ])
                                ->native(false),

                            Select::make('category')
                                ->label('Kategorija')
                                ->options([
                                    'tops'        => 'Tops (majice, bluze, košulje)',
                                    'bottoms'     => 'Bottoms (pantalone, suknje)',
                                    'jackets'     => 'Jackets (jakne, kaputi)',
                                    'dresses'     => 'Dresses (haljine)',
                                    'shoes'       => 'Shoes (cipele)',
                                    'bags'        => 'Bags (torbe)',
                                    'accessories' => 'Accessories (aksesoari)',
                                    'jewelry'     => 'Jewelry (nakit)',
                                    'activewear'  => 'Activewear (sportska odjeća)',
                                    'occasion'    => 'Occasion (svečana odjeća)',
                                    'swimwear'    => 'Swimwear (kupaći kostimi)',
                                ])
                                ->searchable()
                                ->native(false),

                            TextInput::make('subcategory')
                                ->label('Podkategorija')
                                ->maxLength(128),

                            TextInput::make('size')
                                ->label('Veličina')
                                ->maxLength(20),

                            Select::make('condition')
                                ->label('Stanje')
                                ->options([
                                    'new'       => 'Novo/Nenošeno',
                                    'very_good' => 'Velmi dobro',
                                    'good'      => 'Dobro',
                                    'worn'      => 'Vidljivo nošeno',
                                ])
                                ->native(false),

                            TextInput::make('price')
                                ->label('Cijena')
                                ->numeric()
                                ->suffix('KM'),

                            TextInput::make('color')->label('Boja')->maxLength(40),

                            TextInput::make('material')->label('Materijal')->maxLength(100),
                        ]),
                    ]),

                Section::make('Status')
                    ->columnSpan(4)
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft'          => 'Draft',
                                'pending_review' => 'Pending review',
                                'active'         => 'Active',
                                'reserved'       => 'Reserved',
                                'sold'           => 'Sold',
                            ])
                            ->native(false),

                        Select::make('seller_id')
                            ->label('Prodavac')
                            ->relationship('seller', 'username')
                            ->searchable()
                            ->preload()
                            ->disabled(),
                    ]),

                Section::make('Dostava & opcije')
                    ->columnSpan(12)
                    ->schema([
                        Grid::make(4)->schema([
                            Select::make('shipping_size')
                                ->label('Veličina paketa')
                                ->options(['S' => 'S', 'M' => 'M', 'L' => 'L'])
                                ->native(false),

                            TextInput::make('exact_shipping_price')
                                ->label('Tačna cijena dostave')
                                ->numeric()
                                ->suffix('KM'),

                            TextInput::make('location')
                                ->label('Lokacija')
                                ->maxLength(100),
                        ]),

                        Grid::make(4)->schema([
                            Toggle::make('allows_trades')->label('Dozvoljava zamjene'),
                            Toggle::make('allows_offers')->label('Dozvoljava ponude'),
                            Toggle::make('pickup_enabled')->label('Lično preuzimanje'),
                            Toggle::make('free_shipping')->label('Besplatna dostava'),
                        ]),
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

                Section::make('Designer')
                    ->columnSpan(12)
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('designer_status')
                                ->label('Designer status')
                                ->options([
                                    'pending'  => 'Na čekanju',
                                    'approved' => 'Odobreno',
                                    'rejected' => 'Odbijeno',
                                ])
                                ->placeholder('— Nije designer —')
                                ->native(false)
                                ->helperText('Postavi na "Odobreno" da odmah dobiješ badge bez review procesa.'),

                            TextInput::make('designer_brand')
                                ->label('Brand (od prodavca)')
                                ->maxLength(128)
                                ->placeholder('npr. Gucci, Prada, Chanel'),

                            TextInput::make('designer_reject_reason')
                                ->label('Razlog odbijanja')
                                ->maxLength(500)
                                ->placeholder('Ostavi prazno ako nema odbijanja'),
                        ]),

                        Textarea::make('designer_notes')
                            ->label('Opis (od prodavca)')
                            ->rows(3)
                            ->maxLength(1000)
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
