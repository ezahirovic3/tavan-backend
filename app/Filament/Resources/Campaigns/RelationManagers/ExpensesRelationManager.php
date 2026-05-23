<?php

namespace App\Filament\Resources\Campaigns\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';

    protected static ?string $title = 'Troškovi';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('amount')
                ->label('Iznos (KM)')
                ->required()
                ->numeric()
                ->minValue(0.01)
                ->step(0.01),

            DatePicker::make('spent_at')
                ->label('Datum')
                ->required()
                ->native(false)
                ->default(now()),

            TextInput::make('note')
                ->label('Napomena')
                ->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount')
            ->columns([
                TextColumn::make('spent_at')
                    ->label('Datum')
                    ->date('d.m.Y.')
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Iznos')
                    ->state(fn ($record) => number_format($record->amount, 2) . ' KM')
                    ->alignEnd()
                    ->weight('semibold'),

                TextColumn::make('note')
                    ->label('Napomena')
                    ->color('gray')
                    ->limit(60),
            ])
            ->headerActions([
                CreateAction::make()->label('Novi trošak'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('spent_at', 'desc');
    }
}
