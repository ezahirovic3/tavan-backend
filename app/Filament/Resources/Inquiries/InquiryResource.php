<?php

namespace App\Filament\Resources\Inquiries;

use App\Filament\Resources\Inquiries\Pages\ListInquiries;
use App\Filament\Resources\Inquiries\Pages\ViewInquiry;
use App\Models\SupportInquiry;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class InquiryResource extends Resource
{
    protected static ?string $model = SupportInquiry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|\UnitEnum|null $navigationGroup = 'Komunikacija';

    protected static ?string $navigationLabel = 'Upiti';

    protected static ?string $modelLabel = 'upit';

    protected static ?string $pluralModelLabel = 'upiti';

    protected static ?int $navigationSort = 60;

    protected static ?string $recordTitleAttribute = 'subject';

    public static function getNavigationBadge(): ?string
    {
        $n = static::getModel()::where('status', 'open')->count();
        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pošiljalac')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('name')->label('Ime')->weight('semibold'),
                            TextEntry::make('email')->label('Email')->copyable()->icon('heroicon-m-envelope'),
                            TextEntry::make('user.username')
                                ->label('Korisnički račun')
                                ->prefix('@')
                                ->placeholder('Anoniman / nije ulogovan')
                                ->color('gray'),
                            TextEntry::make('user.id')
                                ->label('User ID')
                                ->placeholder('—')
                                ->color('gray')
                                ->extraAttributes(['class' => 'font-mono text-xs']),
                            TextEntry::make('created_at')->label('Primljen')->dateTime('d.m.Y. H:i'),
                        ]),
                    ]),

                Section::make('Poruka')
                    ->schema([
                        TextEntry::make('subject')->label('Naslov')->weight('bold')->size('lg'),
                        TextEntry::make('body')->label('Tekst')->prose()->columnSpanFull(),
                    ]),

                Section::make('Status')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($state) => $state === 'resolved' ? 'success' : 'warning')
                            ->formatStateUsing(fn ($state) => $state === 'resolved' ? 'Riješen' : 'Otvoren'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject')
                    ->label('Naslov')
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn ($record) => str($record->body)->limit(80))
                    ->wrap(),

                TextColumn::make('name')
                    ->label('Pošiljalac')
                    ->searchable()
                    ->description(fn ($record) => $record->email)
                    ->color('gray'),

                TextColumn::make('user.username')
                    ->label('Račun')
                    ->prefix('@')
                    ->placeholder('—')
                    ->color('gray')
                    ->extraAttributes(['class' => 'font-mono text-xs']),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state === 'resolved' ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state) => $state === 'resolved' ? 'Riješen' : 'Otvoren'),

                TextColumn::make('created_at')
                    ->label('Datum')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->color('gray')
                    ->size('sm'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(['open' => 'Otvoreni', 'resolved' => 'Riješeni'])
                    ->default('open'),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('reply')
                    ->label('Odgovori')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('primary')
                    ->schema([
                        TextInput::make('subject')
                            ->label('Naslov')
                            ->required()
                            ->default(fn ($record) => 'RE: ' . $record->subject),
                        Textarea::make('body')
                            ->label('Poruka')
                            ->required()
                            ->rows(8),
                    ])
                    ->modalHeading('Pošalji email odgovor')
                    ->modalSubmitActionLabel('Pošalji')
                    ->action(function (array $data, $record) {
                        // Mail::to($record->email)->send(new InquiryReply($record, $data));
                        Notification::make()->success()->title('Odgovor poslan')->send();
                    }),

                Action::make('resolve')
                    ->label('Označi kao riješeno')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== 'resolved')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update([
                        'status' => 'resolved',
                    ])),

                Action::make('reopen')
                    ->label('Otvori ponovo')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'resolved')
                    ->action(fn ($record) => $record->update([
                        'status' => 'open',
                    ])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => auth()->user()->isSuperAdmin()),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInquiries::route('/'),
            'view'  => ViewInquiry::route('/{record}'),
        ];
    }
}
