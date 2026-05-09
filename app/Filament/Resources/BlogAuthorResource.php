<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogAuthorResource\Pages;
use App\Models\BlogAuthor;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BlogAuthorResource extends Resource
{
    protected static ?string $model = BlogAuthor::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-user-circle';
    protected static \UnitEnum|string|null $navigationGroup = 'Marketing';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Autor';
    protected static ?string $pluralModelLabel = 'Autori';

    public static function canDelete(Model $record): bool { return auth()->user()?->isSuperAdmin() ?? false; }
    public static function canDeleteAny(): bool           { return auth()->user()?->isSuperAdmin() ?? false; }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Ime autora')
                ->required()
                ->maxLength(100),

            TextInput::make('bio')
                ->label('Kratka biografija')
                ->maxLength(255)
                ->columnSpanFull(),

            FileUpload::make('avatar')
                ->label('Fotografija autora')
                ->image()
                ->disk('r2')
                ->directory('blog/authors')
                ->visibility('public')
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('')
                    ->disk('r2')
                    ->circular()
                    ->size(40),
                TextColumn::make('name')
                    ->label('Ime')
                    ->searchable(),
                TextColumn::make('bio')
                    ->label('Biografija')
                    ->limit(60)
                    ->color('gray'),
                TextColumn::make('posts_count')
                    ->label('Postova')
                    ->counts('posts')
                    ->badge(),
            ])
            ->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBlogAuthors::route('/'),
            'create' => Pages\CreateBlogAuthor::route('/create'),
            'edit'   => Pages\EditBlogAuthor::route('/{record}/edit'),
        ];
    }
}
