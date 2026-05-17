<?php

namespace App\Filament\Resources\SupportConversations\Pages;

use App\Filament\Resources\SupportConversations\SupportConversationResource;
use App\Models\Message;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ViewSupportConversation extends Page
{
    protected static string $resource = SupportConversationResource::class;

    protected string $view = 'filament.resources.support-conversations.view';

    public ?\App\Models\Conversation $record = null;

    public function mount($record): void
    {
        $this->record = SupportConversationResource::getModel()::with('participantOne', 'messages.sender')->findOrFail($record);
    }

    public function getTitle(): string
    {
        return '@' . $this->record->participantOne?->username . ' · razgovor';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label('Odgovori')
                ->icon('heroicon-m-paper-airplane')
                ->color('primary')
                ->schema([
                    Textarea::make('body')
                        ->label('Poruka')
                        ->required()
                        ->rows(6)
                        ->maxLength(2000),
                ])
                ->modalHeading('Pošalji odgovor')
                ->modalSubmitActionLabel('Pošalji')
                ->action(function (array $data) {
                    Message::create([
                        'conversation_id' => $this->record->id,
                        'sender_id'       => auth()->id(),
                        'body'            => $data['body'],
                    ]);
                    $this->record->update(['last_activity_at' => now()]);
                    Notification::make()->success()->title('Poruka poslana')->send();
                    $this->redirect(static::getUrl(['record' => $this->record]));
                }),

            Action::make('toggleReplies')
                ->label(fn () => $this->record->allow_replies ? 'Zaključaj odgovore' : 'Otključaj odgovore')
                ->icon(fn () => $this->record->allow_replies ? 'heroicon-m-lock-closed' : 'heroicon-m-lock-open')
                ->color(fn () => $this->record->allow_replies ? 'gray' : 'success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['allow_replies' => ! $this->record->allow_replies]);
                    $this->redirect(static::getUrl(['record' => $this->record]));
                }),

            Action::make('toggleStatus')
                ->label(fn () => $this->record->status === 'resolved' ? 'Otvori ponovo' : 'Označi kao riješeno')
                ->icon(fn () => $this->record->status === 'resolved' ? 'heroicon-m-arrow-uturn-left' : 'heroicon-m-check-circle')
                ->color(fn () => $this->record->status === 'resolved' ? 'warning' : 'success')
                ->action(function () {
                    $this->record->update([
                        'status' => $this->record->status === 'resolved' ? 'open' : 'resolved',
                    ]);
                    $this->redirect(static::getUrl(['record' => $this->record]));
                }),
        ];
    }
}
