<?php

namespace App\Filament\Resources\Announcements;

use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Filament\Resources\Announcements\Pages\ViewAnnouncement;
use App\Models\Announcement;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|\UnitEnum|null $navigationGroup = 'Komunikacija';

    protected static ?string $navigationLabel = 'Obavještenja';

    protected static ?string $modelLabel = 'obavještenje';

    protected static ?string $pluralModelLabel = 'obavještenja';

    protected static ?int $navigationSort = 62;

    public const TARGETS = [
        'all'                    => 'Svi',
        'verified'               => 'Verificirani',
        'city'                   => 'Grad',
        'listings_require_review' => 'Pregled oglasa',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(8)
            ->components([
                Section::make('Sadržaj')
                    ->columnSpan(5)
                    ->schema([
                        TextInput::make('title')
                            ->label('Naslov')
                            ->required()
                            ->maxLength(140),

                        Textarea::make('body')
                            ->label('Tekst')
                            ->required()
                            ->rows(8)
                            ->maxLength(2000),
                    ]),

                Section::make('Ciljna grupa')
                    ->columnSpan(3)
                    ->schema([
                        Select::make('target_group')
                            ->label('Grupa')
                            ->options(self::TARGETS)
                            ->required()
                            ->live()
                            ->native(false)
                            ->default('all'),

                        TextInput::make('target_value')
                            ->label('Grad')
                            ->placeholder('npr. Sarajevo')
                            ->visible(fn ($get) => $get('target_group') === 'city')
                            ->required(fn ($get) => $get('target_group') === 'city'),
                    ])
                    ->footerActions([]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Naslov')
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn ($record) => str($record->body)->limit(72))
                    ->wrap(),

                TextColumn::make('target_group')
                    ->label('Grupa')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'all'      => 'primary',
                        'verified' => 'success',
                        'city'     => 'info',
                        'pending_review' => 'warning',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state, $record) =>
                        ($state === 'city' && $record->target_city)
                            ? "Grad · {$record->target_city}"
                            : self::TARGETS[$state] ?? $state),

                TextColumn::make('read_count')
                    ->label('Pročitano')
                    ->numeric()
                    ->color('gray')
                    ->size('sm')
                    ->alignEnd(),

                TextColumn::make('creator.name')
                    ->label('Poslao')
                    ->color('gray')
                    ->size('sm'),

                TextColumn::make('sent_at')
                    ->label('Datum')
                    ->dateTime('d.m.Y. H:i')
                    ->sortable()
                    ->color('gray')
                    ->size('sm'),
            ])
            ->filters([
                SelectFilter::make('target_group')->options(self::TARGETS),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => auth()->user()->isSuperAdmin()),
                ]),
            ])
            ->defaultSort('sent_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'view'   => ViewAnnouncement::route('/{record}'),
            'edit'   => EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
