<?php

namespace App\Filament\Resources\BlogPosts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BlogPostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('')
                    ->disk('r2')
                    ->square()
                    ->size(48)
                    ->extraImgAttributes(['class' => 'object-cover']),

                TextColumn::make('title')
                    ->label('Naslov')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn ($record) => str($record->excerpt)->limit(72))
                    ->wrap(),

                TextColumn::make('tag')
                    ->label('Tag')
                    ->badge()
                    ->color('gray')
                    ->extraAttributes(['class' => 'font-mono uppercase tracking-wider text-[10px]']),

                TextColumn::make('is_published')
                    ->label('Status')
                    ->badge()
                    ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-pencil-square')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state ? 'Objavljen' : 'Draft'),

                TextColumn::make('author.name')
                    ->label('Autor')
                    ->color('gray')
                    ->size('sm'),

                TextColumn::make('published_at')
                    ->label('Datum')
                    ->dateTime('d.m.Y.')
                    ->color('gray')
                    ->size('sm')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('Status')
                    ->trueLabel('Samo objavljeni')
                    ->falseLabel('Samo draft')
                    ->placeholder('Svi'),
                SelectFilter::make('tag')
                    ->label('Tag')
                    ->options(fn () => \App\Models\BlogPost::query()
                        ->whereNotNull('tag')->distinct()->pluck('tag', 'tag')->toArray()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => auth()->user()->isSuperAdmin()),
                ]),
            ])
            ->defaultSort('published_at', 'desc')
            ->striped(false);
    }
}
