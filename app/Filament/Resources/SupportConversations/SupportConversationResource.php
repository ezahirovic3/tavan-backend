<?php

namespace App\Filament\Resources\SupportConversations;

use App\Filament\Resources\SupportConversations\Pages\CreateSupportConversation;
use App\Filament\Resources\SupportConversations\Pages\ListSupportConversations;
use App\Filament\Resources\SupportConversations\Pages\ViewSupportConversation;
use App\Models\Conversation;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
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
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

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
                            ->options(fn () => User::where('is_system', false)
                                ->whereNotIn('role', ['admin', 'super_admin'])
                                ->orderBy('username')
                                ->limit(500)
                                ->get()
                                ->mapWithKeys(fn ($u) => [$u->id => '@' . ($u->username ?? '?') . ' · ' . ($u->name ?? '—')])
                            )
                            ->searchable()
                            ->required(),

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
            ->modifyQueryUsing(fn ($query) => $query->where('type', 'admin_support')
                ->with(['participantOne', 'lastMessage']))
            ->columns([
                TextColumn::make('participantOne.name')
                    ->label('Korisnik')
                    ->description(fn ($record) => '@' . $record->participantOne?->username)
                    ->searchable(query: fn ($query, string $search) => $query->whereHas(
                        'participantOne',
                        fn ($q) => $q->where('name', 'like', "%{$search}%")
                                     ->orWhere('username', 'like', "%{$search}%")
                    ))
                    ->weight('semibold'),

                TextColumn::make('lastMessage.body')
                    ->label('Posljednja poruka')
                    ->formatStateUsing(function ($record) {
                        $msg = $record->lastMessage;
                        if (! $msg) {
                            return '—';
                        }

                        return match ($msg->type) {
                            'image' => '📷 Slika',
                            default => str($msg->body ?? '')->limit(60)->toString(),
                        };
                    })
                    ->color('gray')
                    ->wrap()
                    ->size('sm'),

                TextColumn::make('last_sender')
                    ->label('Zadnje poslao')
                    ->state(fn ($record) => $record->lastMessage
                        ? ($record->lastMessage->sender_id === $record->participant_one_id ? 'Korisnik' : 'Podrška')
                        : '—')
                    ->badge()
                    ->color(fn ($state) => $state === 'Korisnik' ? 'warning' : 'gray'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state === 'resolved' ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state) => $state === 'resolved' ? 'Riješen' : 'Čeka odgovor')
                    ->sortable(),

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
                SelectFilter::make('status')->label('Status')->options([
                    'open' => 'Čeka odgovor',
                    'resolved' => 'Riješeni',
                ])->default('open'),
                TernaryFilter::make('allow_replies')->label('Odgovori')->placeholder('Svi'),
            ])
            ->recordActions([
                Action::make('quickResolve')
                    ->label('Riješi')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (Conversation $record) => $record->status !== 'resolved')
                    ->action(fn (Conversation $record) => $record->update(['status' => 'resolved']))
                    ->successNotificationTitle('Razgovor označen kao riješen'),

                Action::make('quickReopen')
                    ->label('Otvori')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (Conversation $record) => $record->status === 'resolved')
                    ->action(fn (Conversation $record) => $record->update(['status' => 'open']))
                    ->successNotificationTitle('Razgovor ponovo otvoren'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulkResolve')
                        ->label('Označi kao riješeno')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            Conversation::whereIn('id', $records->pluck('id'))->update(['status' => 'resolved']);
                            Notification::make()->success()->title('Odabrani razgovori označeni kao riješeni')->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('bulkReopen')
                        ->label('Otvori ponovo')
                        ->icon('heroicon-m-arrow-uturn-left')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            Conversation::whereIn('id', $records->pluck('id'))->update(['status' => 'open']);
                            Notification::make()->success()->title('Odabrani razgovori ponovo otvoreni')->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
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
