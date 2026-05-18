<?php

namespace App\Filament\Resources\SupportConversations\Pages;

use App\Filament\Resources\SupportConversations\SupportConversationResource;
use App\Models\Conversation;
use App\Models\Message;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

class ViewSupportConversation extends Page
{
    protected static string $resource = SupportConversationResource::class;

    protected string $view = 'filament.resources.support-conversations.view';

    #[Locked]
    public Model | int | string | null $record = null;

    public function mount(string $record): void
    {
        $this->record = Conversation::with('participantOne', 'messages.sender')->findOrFail($record);
    }

    public function getRecord(): Conversation
    {
        if (! $this->record instanceof Conversation) {
            $this->record = Conversation::with('participantOne', 'messages.sender')->findOrFail($this->record);
        }

        return $this->record;
    }

    public function getTitle(): string
    {
        return '@' . $this->getRecord()->participantOne?->username . ' · razgovor';
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
                    $record = $this->getRecord();
                    Message::create([
                        'conversation_id' => $record->id,
                        'sender_id'       => auth()->id(),
                        'body'            => $data['body'],
                    ]);
                    $record->update(['last_activity_at' => now()]);
                    Notification::make()->success()->title('Poruka poslana')->send();
                    $this->redirect(static::getUrl(['record' => $record]));
                }),

            Action::make('toggleReplies')
                ->label(fn () => $this->getRecord()->allow_replies ? 'Zaključaj odgovore' : 'Otključaj odgovore')
                ->icon(fn () => $this->getRecord()->allow_replies ? 'heroicon-m-lock-closed' : 'heroicon-m-lock-open')
                ->color(fn () => $this->getRecord()->allow_replies ? 'gray' : 'success')
                ->requiresConfirmation()
                ->action(function () {
                    $record = $this->getRecord();
                    $record->update(['allow_replies' => ! $record->allow_replies]);
                    $this->redirect(static::getUrl(['record' => $record]));
                }),

            Action::make('toggleStatus')
                ->label(fn () => $this->getRecord()->status === 'resolved' ? 'Otvori ponovo' : 'Označi kao riješeno')
                ->icon(fn () => $this->getRecord()->status === 'resolved' ? 'heroicon-m-arrow-uturn-left' : 'heroicon-m-check-circle')
                ->color(fn () => $this->getRecord()->status === 'resolved' ? 'warning' : 'success')
                ->action(function () {
                    $record = $this->getRecord();
                    $record->update([
                        'status' => $record->status === 'resolved' ? 'open' : 'resolved',
                    ]);
                    $this->redirect(static::getUrl(['record' => $record]));
                }),
        ];
    }
}
