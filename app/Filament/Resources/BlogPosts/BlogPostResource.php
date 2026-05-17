<?php

namespace App\Filament\Resources\BlogPosts;

use App\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Filament\Resources\BlogPosts\Pages\EditBlogPost;
use App\Filament\Resources\BlogPosts\Pages\ListBlogPosts;
use App\Filament\Resources\BlogPosts\Schemas\BlogPostForm;
use App\Filament\Resources\BlogPosts\Tables\BlogPostsTable;
use App\Models\BlogPost;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Sadržaj';

    protected static ?string $navigationLabel = 'Blog Posts';

    protected static ?string $modelLabel = 'post';

    protected static ?string $pluralModelLabel = 'postovi';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return BlogPostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlogPostsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListBlogPosts::route('/'),
            'create' => CreateBlogPost::route('/create'),
            'edit'   => EditBlogPost::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'excerpt', 'tag'];
    }
}
