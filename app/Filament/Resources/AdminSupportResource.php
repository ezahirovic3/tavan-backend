<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminSupportResource\Pages;
use App\Models\Conversation;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdminSupportResource extends Resource
{
    protected static ?string $model = Conversation::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static \UnitEnum|string|null $navigationGroup = 'Podrška';
    protected static ?int $navigationSort = 2;
    protected static ?string $label = 'Support konverzacija';
    protected static ?string $pluralLabel = 'Support konverzacije';

    // ── Permissions ───────────────────────────────────────────────────────────

    public static function canCreate(): bool              { return false; }
    public static function canEdit(Model $record): bool   { return false; }
    public static function canDelete(Model $record): bool { return auth()->user()?->isSuperAdmin() ?? false; }
    public static function canDeleteAny(): bool           { return auth()->user()?->isSuperAdmin() ?? false; }

    // ── Base query — only admin_support conversations ─────────────────────────

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('type', 'admin_support')
            ->with(['participantOne', 'participantTwo', 'lastMessage']);
    }

    // ── Form (unused) ─────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    // ── Infolist (conversation view) ──────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        $systemId = config('tavan.system_user_id');

        return $schema->schema([
            Section::make('Korisnik')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('user_name')
                        ->label('Ime')
                        ->state(fn (Conversation $record) => $record->participantOne->id === $systemId
                            ? $record->participantTwo->name
                            : $record->participantOne->name
                        ),
                    TextEntry::make('user_email')
                        ->label('Email')
                        ->state(fn (Conversation $record) => $record->participantOne->id === $systemId
                            ? $record->participantTwo->email
                            : $record->participantOne->email
                        )
                        ->copyable(),
                    TextEntry::make('user_username')
                        ->label('Username')
                        ->state(fn (Conversation $record) => '@' . (
                            $record->participantOne->id === $systemId
                                ? $record->participantTwo->username
                                : $record->participantOne->username
                        )),
                ]),
                Grid::make(3)->schema([
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn (?string $state) => $state === 'resolved' ? 'success' : 'warning')
                        ->formatStateUsing(fn (?string $state) => $state === 'resolved' ? 'Riješeno' : 'Otvoreno'),
                    TextEntry::make('allow_replies')
                        ->label('Odgovori')
                        ->badge()
                        ->color(fn (?bool $state) => $state ? 'success' : 'danger')
                        ->formatStateUsing(fn (?bool $state) => $state ? 'Dozvoljeni' : 'Onemogućeni'),
                    TextEntry::make('last_message_at')
                        ->label('Posljednja poruka')
                        ->dateTime('d.m.Y H:i'),
                ]),
            ]),

            Section::make('Razgovor')->schema([
                RepeatableEntry::make('thread')
                    ->label('')
                    ->state(fn (Conversation $record) => $record->messages()
                        ->with('sender')
                        ->oldest('created_at')
                        ->get()
                        ->map(fn ($m) => [
                            'sender'   => $m->sender_id === $systemId
                                ? 'Tavan Podrška'
                                : ($m->sender->name ?? 'Korisnik'),
                            'body'     => $m->body ?? '—',
                            'time'     => $m->created_at->format('d.m.Y H:i'),
                            'is_admin' => $m->sender_id === $systemId,
                        ])
                        ->all()
                    )
                    ->schema([
                        TextEntry::make('sender')
                            ->label('')
                            ->badge()
                            ->color(fn (string $state) => $state === 'Tavan Podrška' ? 'primary' : 'gray'),
                        TextEntry::make('body')
                            ->label('')
                            ->columnSpan(3),
                        TextEntry::make('time')
                            ->label('')
                            ->color('gray'),
                    ])
                    ->columns(5),
            ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        $systemId = config('tavan.system_user_id');

        return $table
            ->columns([
                TextColumn::make('user')
                    ->label('Korisnik')
                    ->state(fn (Conversation $record) => $record->participantOne->id === $systemId
                        ? $record->participantTwo->name
                        : $record->participantOne->name
                    )
                    ->searchable(query: fn (Builder $query, string $search) =>
                        $query->whereHas('participantOne', fn ($q) => $q->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%"))
                              ->orWhereHas('participantTwo', fn ($q) => $q->where('name', 'like', "%{$search}%")
                                  ->orWhere('username', 'like', "%{$search}%"))
                    ),

                TextColumn::make('last_message_preview')
                    ->label('Posljednja poruka')
                    ->state(fn (Conversation $record) => $record->lastMessage?->body
                        ? \Illuminate\Support\Str::limit($record->lastMessage->body, 60)
                        : '—'
                    )
                    ->color('gray'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state) => $state === 'resolved' ? 'success' : 'warning')
                    ->formatStateUsing(fn (?string $state) => $state === 'resolved' ? 'Riješeno' : 'Otvoreno'),

                TextColumn::make('allow_replies')
                    ->label('Odgovori')
                    ->badge()
                    ->color(fn (?bool $state) => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn (?bool $state) => $state ? 'Da' : 'Ne'),

                TextColumn::make('last_message_at')
                    ->label('Aktivnost')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(['open' => 'Otvoreno', 'resolved' => 'Riješeno']),
            ])
            ->actions([ViewAction::make()])
            ->defaultSort('last_message_at', 'desc');
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminSupport::route('/'),
            'view'  => Pages\ViewAdminSupport::route('/{record}'),
        ];
    }
}
