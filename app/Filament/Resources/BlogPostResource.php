<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages;
use App\Models\BlogPost;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-newspaper';
    protected static \UnitEnum|string|null $navigationGroup = 'Marketing';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('title')
                ->label('Naslov')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state)))
                ->columnSpanFull(),

            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            Select::make('tag')
                ->label('Kategorija')
                ->required()
                ->options([
                    'Savjeti'   => 'Savjeti',
                    'Moda'      => 'Moda',
                    'Zajednica' => 'Zajednica',
                    'Tavan'     => 'Tavan',
                ]),

            Textarea::make('excerpt')
                ->label('Kratki opis')
                ->required()
                ->maxLength(300)
                ->rows(2)
                ->columnSpanFull(),

            RichEditor::make('content')
                ->label('Sadržaj')
                ->columnSpanFull()
                ->toolbarButtons([
                    'blockquote', 'bold', 'bulletList', 'codeBlock',
                    'h2', 'h3', 'italic', 'link', 'orderedList',
                    'redo', 'strike', 'underline', 'undo',
                ]),

            TextInput::make('read_time')
                ->label('Vrijeme čitanja')
                ->placeholder('npr. 4 min')
                ->maxLength(20)
                ->default('3 min'),

            FileUpload::make('cover_image')
                ->label('Naslovna slika')
                ->image()
                ->disk('r2')
                ->directory('blog')
                ->visibility('public')
                ->nullable(),

            ColorPicker::make('cover_color')
                ->label('Boja pozadine (fallback)')
                ->nullable(),

            Toggle::make('is_published')
                ->label('Objavljeno')
                ->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Naslov')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('tag')
                    ->label('Kategorija')
                    ->badge(),
                TextColumn::make('read_time')
                    ->label('Čitanje')
                    ->color('gray'),
                IconColumn::make('is_published')
                    ->label('Objavljeno')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label('Datum objave')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_published')->label('Objavljeno'),
            ])
            ->actions([EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('published_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit'   => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
