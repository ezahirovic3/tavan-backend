<?php

namespace App\Filament\Resources\SupportConversations;

use App\Filament\Resources\SupportConversations\Pages\CreateSupportConversation;
use App\Filament\Resources\SupportConversations\Pages\ListSupportConversations;
use App\Filament\Resources\SupportConversations\Pages\ViewSupportConversation;
use App\Models\Conversation;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

class SupportConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = 'Komunikacija';

    protected static ?string $navigationLabel = 'Podrška · razgovori';

    protected static ?string $modelLabel = 'razgovor';

    protected static ?string $pluralModelLabel = 'razgovori';

    protected static ?int $navigationSort = 61;

    public static function getNavigationBadge(): ?string
    {
        $n = static::getModel()::where('status', 'open')->where('type', 'admin_support')->count();
        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Novi razgovor')
                    ->description('Pokreni razgovor sa korisnikom u ime „Tavan Podrška".')
                    ->schema([
                        Select::make('participant_one_id')
                            ->label('Korisnik')
                            ->relationship('participantOne', 'username')
                            ->searchable(['username', 'name', 'email'])
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn ($r) => '@' . ($r->username ?? '?') . ' · ' . ($r->name ?? '—')),

                        Textarea::make('initial_message')
                            ->label('Otvarajuća poruka')
                            ->rows(6)
                            ->required()
                            ->maxLength(2000),

                        Toggle::make('allow_replies')
                            ->label('Dozvoli odgovore korisnika')
                            ->default(true)
                            ->onColor('success'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->where('type', 'admin_support'))
            ->columns([
                TextColumn::make('participantOne.name')
                    ->label('Korisnik')
                    ->description(fn ($record) => '@' . $record->participantOne?->username)
                    ->searchable(['users.name', 'users.username'])
                    ->weight('semibold'),

                TextColumn::make('lastMessage.body')
                    ->label('Posljednja poruka')
                    ->limit(60)
                    ->color('gray')
                    ->wrap()
                    ->size('sm'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state === 'resolved' ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state) => $state === 'resolved' ? 'Riješen' : 'Otvoren'),

                IconColumn::make('allow_replies')
                    ->label('Odgovori')
                    ->boolean()
                    ->trueIcon('heroicon-m-lock-open')
                    ->trueColor('success')
                    ->falseIcon('heroicon-m-lock-closed')
                    ->falseColor('gray'),

                TextColumn::make('last_message_at')
                    ->label('Aktivnost')
                    ->since()
                    ->color('gray')
                    ->size('sm')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'open' => 'Otvoreni',
                    'resolved' => 'Riješeni',
                ])->default('open'),
                TernaryFilter::make('allow_replies')->label('Odgovori')->placeholder('Svi'),
            ])
            ->defaultSort('last_message_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListSupportConversations::route('/'),
            'create' => CreateSupportConversation::route('/create'),
            'view'   => ViewSupportConversation::route('/{record}'),
        ];
    }
}
