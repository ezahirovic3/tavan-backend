<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Osnovno')
                    ->columnSpan(8)
                    ->schema([
                        TextInput::make('name')
                            ->label('Naziv kampanje')
                            ->required()
                            ->maxLength(180),

                        Textarea::make('description')
                            ->label('Napomena')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),

                Section::make('Postavke')
                    ->columnSpan(4)
                    ->schema([
                        Select::make('channel')
                            ->label('Kanal')
                            ->required()
                            ->options([
                                'instagram'  => 'Instagram',
                                'facebook'   => 'Facebook',
                                'tiktok'     => 'TikTok',
                                'flyer'      => 'Flyer / sticker',
                                'influencer' => 'Influencer',
                                'other'      => 'Ostalo',
                            ]),

                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'active'    => 'Aktivna',
                                'paused'    => 'Pauzirana',
                                'completed' => 'Završena',
                            ])
                            ->default('active'),

                        DatePicker::make('starts_at')
                            ->label('Počinje')
                            ->native(false),

                        DatePicker::make('ends_at')
                            ->label('Završava')
                            ->native(false),
                    ]),
            ]);
    }
}
