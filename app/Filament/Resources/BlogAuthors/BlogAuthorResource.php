<?php

namespace App\Filament\Resources\BlogAuthors;

use App\Filament\Resources\BlogAuthors\Pages\ManageBlogAuthors;
use App\Models\BlogAuthor;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BlogAuthorResource extends Resource
{
    protected static ?string $model = BlogAuthor::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Sadržaj';

    protected static ?string $navigationLabel = 'Autori';

    protected static ?string $modelLabel = 'autor';

    protected static ?string $pluralModelLabel = 'autori';

    protected static ?int $navigationSort = 11; // Right after Blog Posts (10)

    protected static ?string $recordTitleAttribute = 'name';

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)->schema([

                    // ───── Avatar (left rail) ─────
                    FileUpload::make('avatar')
                        ->label('Fotografija')
                        ->image()
                        ->avatar()
                        ->disk('r2')
                        ->directory('blog/authors')
                        ->visibility('public')
                        ->imageEditor()
                        ->imageEditorAspectRatios(['1:1'])
                        ->circleCropper()
                        ->helperText('Kvadratna, min 256×256 px.')
                        ->columnSpan(4),

                    // ───── Profile (right) ─────
                    Grid::make(1)
                        ->columnSpan(8)
                        ->schema([
                            TextInput::make('name')
                                ->label('Ime autora')
                                ->required()
                                ->maxLength(100)
                                ->autofocus()
                                ->placeholder('npr. Lejla Kapidžić'),

                            Textarea::make('bio')
                                ->label('Kratka biografija')
                                ->rows(3)
                                ->maxLength(255)
                                ->helperText('Prikazuje se u kartici autora ispod posta. Max 255 znakova.')
                                ->extraAttributes(['style' => 'resize:vertical']),
                        ]),
                ]),
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
                    ->size(40)
                    ->defaultImageUrl(fn ($record) =>
                        'https://ui-avatars.com/api/?name=' . urlencode($record->name ?? '?') .
                        '&background=121212&color=fff&bold=true&size=128'),

                TextColumn::make('name')
                    ->label('Autor')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn ($record) => $record->bio
                        ? str($record->bio)->limit(80)
                        : null)
                    ->wrap(),

                TextColumn::make('posts_count')
                    ->label('Postova')
                    ->counts('posts')
                    ->badge()
                    ->color('gray')
                    ->extraAttributes(['class' => 'font-mono tabular-nums'])
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('created_at')
                    ->label('Dodano')
                    ->date('d.m.Y.')
                    ->color('gray')
                    ->size('sm')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()->slideOver(),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()?->isSuperAdmin() ?? false)
                    ->requiresConfirmation()
                    ->modalHeading('Obriši autora?')
                    ->modalDescription(fn ($record) => "Postova povezanih s ovim autorom: " . $record->posts()->count() . ". Postovi neće biti obrisani."),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->isSuperAdmin() ?? false),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->emptyStateHeading('Još nema autora')
            ->emptyStateDescription('Dodaj prvog autora prije objave bloga.')
            ->emptyStateIcon('heroicon-o-user-circle');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBlogAuthors::route('/'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'bio'];
    }
}
