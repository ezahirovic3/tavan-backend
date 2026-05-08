<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages;
use App\Models\BlogPost;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
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

            // ── Meta ──────────────────────────────────────────────────────────
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

            // ── Content blocks ────────────────────────────────────────────────
            Repeater::make('blocks')
                ->label('Sadržaj')
                ->addActionLabel('Dodaj blok')
                ->columnSpanFull()
                ->collapsible()
                ->cloneable()
                ->itemLabel(fn (array $state): string => match ($state['type'] ?? '') {
                    'paragraph'  => '¶ Paragraf',
                    'heading'    => '## Naslov',
                    'subheading' => '### Podnaslov',
                    'quote'      => '❝ Citat',
                    'image'      => '🖼 Slika',
                    'instagram'  => '📷 Instagram',
                    'youtube'    => '▶ YouTube',
                    default      => 'Blok',
                })
                ->schema([
                    Select::make('type')
                        ->label('Tip bloka')
                        ->required()
                        ->live()
                        ->options([
                            'paragraph'  => 'Paragraf',
                            'heading'    => 'Naslov (H2)',
                            'subheading' => 'Podnaslov (H3)',
                            'quote'      => 'Citat',
                            'image'      => 'Slika',
                            'instagram'  => 'Instagram embed',
                            'youtube'    => 'YouTube video',
                        ]),

                    // ── Text field — paragraph / heading / subheading / quote ──
                    Textarea::make('text')
                        ->label('Tekst')
                        ->rows(4)
                        ->visible(fn (Get $get) => in_array($get('type'), [
                            'paragraph', 'heading', 'subheading', 'quote',
                        ])),

                    // ── Quote attribution ─────────────────────────────────────
                    TextInput::make('author')
                        ->label('Autor citata')
                        ->placeholder('npr. Tavan tim')
                        ->visible(fn (Get $get) => $get('type') === 'quote'),

                    // ── Image upload ──────────────────────────────────────────
                    FileUpload::make('file')
                        ->label('Slika')
                        ->image()
                        ->disk('r2')
                        ->directory('blog/images')
                        ->visibility('public')
                        ->visible(fn (Get $get) => $get('type') === 'image'),

                    TextInput::make('caption')
                        ->label('Opis slike (opcionalno)')
                        ->visible(fn (Get $get) => $get('type') === 'image'),

                    // ── Embed URL — instagram / youtube ───────────────────────
                    TextInput::make('url')
                        ->label(fn (Get $get) => $get('type') === 'youtube'
                            ? 'YouTube URL'
                            : 'Instagram URL objave')
                        ->placeholder(fn (Get $get) => $get('type') === 'youtube'
                            ? 'https://www.youtube.com/watch?v=...'
                            : 'https://www.instagram.com/p/...')
                        ->url()
                        ->visible(fn (Get $get) => in_array($get('type'), ['instagram', 'youtube'])),
                ]),

            // ── Cover ─────────────────────────────────────────────────────────
            FileUpload::make('cover_image')
                ->label('Naslovna slika')
                ->image()
                ->disk('r2')
                ->directory('blog')
                ->visibility('public')
                ->nullable(),

            ColorPicker::make('cover_color')
                ->label('Boja pozadine (fallback ako nema slike)')
                ->nullable(),

            // ── Author ────────────────────────────────────────────────────────
            TextInput::make('author_name')
                ->label('Autor')
                ->default('Tavan tim')
                ->maxLength(100),

            FileUpload::make('author_avatar')
                ->label('Autorova fotografija')
                ->image()
                ->disk('r2')
                ->directory('blog/authors')
                ->visibility('public')
                ->nullable(),

            // ── Publishing ────────────────────────────────────────────────────
            TextInput::make('read_time')
                ->label('Vrijeme čitanja')
                ->placeholder('npr. 4 min')
                ->maxLength(20)
                ->default('3 min'),

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
