<?php

namespace App\Filament\Resources\BlogPosts\Schemas;

use App\Models\BlogAuthor;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BlogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([

                // ────────────── MAIN COLUMN ──────────────
                Section::make('Naslov')
                    ->columnSpan(8)
                    ->schema([
                        TextInput::make('title')
                            ->label('Naslov')
                            ->required()
                            ->maxLength(180)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state ?? ''))),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(180)
                            ->prefix('/blog/')
                            ->extraInputAttributes(['class' => 'font-mono']),

                        Textarea::make('excerpt')
                            ->label('Kratki izvod')
                            ->rows(3)
                            ->maxLength(280)
                            ->helperText('Pojavljuje se u listingu i preview karticama.'),
                    ]),

                Section::make('Tijelo posta')
                    ->description('Block builder · heading, subheading, paragraph, image, Instagram embed, YouTube embed')
                    ->columnSpan(8)
                    ->schema([
                        Builder::make('body')
                            ->label('')
                            ->blockNumbers(false)
                            ->collapsible()
                            ->collapsed()
                            ->cloneable()
                            ->blocks([
                                Block::make('heading')
                                    ->label('Heading (H2)')
                                    ->icon('heroicon-o-h1')
                                    ->schema([
                                        TextInput::make('text')->required()->maxLength(180),
                                    ]),

                                Block::make('subheading')
                                    ->label('Subheading (H3)')
                                    ->icon('heroicon-o-h2')
                                    ->schema([
                                        TextInput::make('text')->required()->maxLength(180),
                                    ]),

                                Block::make('paragraph')
                                    ->label('Paragraph')
                                    ->icon('heroicon-o-bars-3-bottom-left')
                                    ->schema([
                                        Textarea::make('text')->required()->rows(5),
                                    ]),

                                Block::make('image')
                                    ->label('Slika')
                                    ->icon('heroicon-o-photo')
                                    ->schema([
                                        FileUpload::make('image')
                                            ->image()
                                            ->disk('r2')
                                            ->directory('blog/inline')
                                            ->imageEditor()
                                            ->required(),
                                        TextInput::make('caption')->label('Caption')->maxLength(180),
                                        TextInput::make('alt')->label('Alt tekst')->maxLength(180),
                                    ]),

                                Block::make('instagram')
                                    ->label('Instagram embed')
                                    ->icon('heroicon-o-camera')
                                    ->schema([
                                        TextInput::make('url')
                                            ->label('Instagram URL')
                                            ->required()
                                            ->url()
                                            ->placeholder('https://www.instagram.com/p/...'),
                                    ]),

                                Block::make('youtube')
                                    ->label('YouTube embed')
                                    ->icon('heroicon-o-play')
                                    ->schema([
                                        TextInput::make('url')
                                            ->label('YouTube URL')
                                            ->required()
                                            ->url()
                                            ->placeholder('https://www.youtube.com/watch?v=...'),
                                    ]),
                            ])
                            ->addActionLabel('+ Dodaj blok'),
                    ]),

                // ────────────── SIDE RAIL ──────────────
                Section::make('Status')
                    ->columnSpan(4)
                    ->schema([
                        Toggle::make('published')
                            ->label('Objavljen')
                            ->onColor('success')
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                if ($state && ! $get('published_at')) {
                                    $set('published_at', now());
                                }
                            }),

                        DateTimePicker::make('published_at')
                            ->label('Datum objave')
                            ->seconds(false)
                            ->native(false),
                    ]),

                Section::make('Cover')
                    ->columnSpan(4)
                    ->schema([
                        FileUpload::make('cover_image')
                            ->label('Cover slika')
                            ->image()
                            ->disk('r2')
                            ->directory('blog/covers')
                            ->imageEditor()
                            ->imageEditorAspectRatios(['16:9', '4:3', '1:1'])
                            ->required(),

                        ColorPicker::make('cover_color')
                            ->label('Cover boja')
                            ->default('#FB5C90')
                            ->helperText('Korištena kao fallback i za UI naglaske.'),
                    ]),

                Section::make('Metapodaci')
                    ->columnSpan(4)
                    ->schema([
                        TextInput::make('tag')
                            ->label('Tag')
                            ->maxLength(64)
                            ->placeholder('vintage, styling, ...'),

                        TextInput::make('read_time')
                            ->label('Vrijeme čitanja')
                            ->numeric()
                            ->suffix('min')
                            ->minValue(1)
                            ->maxValue(60),
                    ]),

                Section::make('Autor')
                    ->columnSpan(4)
                    ->schema([
                        Select::make('blog_author_id')
                            ->label('Autor')
                            ->relationship('author', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Autori se upravljaju na stranici Autori.'),
                    ]),
            ]);
    }
}
