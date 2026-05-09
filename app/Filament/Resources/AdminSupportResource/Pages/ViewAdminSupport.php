<?php

namespace App\Filament\Resources\AdminSupportResource\Pages;

use App\Filament\Resources\AdminSupportResource;
use App\Models\Conversation;
use App\Services\ConversationService;
use App\Services\PushNotificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;

class ViewAdminSupport extends ViewRecord
{
    protected static string $resource = AdminSupportResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Conversation $conversation */
        $conversation = $this->record;

        return [
            Action::make('reply')
                ->label('Odgovori')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->form([
                    Textarea::make('body')
                        ->label('Poruka')
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data) use ($conversation) {
                    $service = app(ConversationService::class);
                    $push    = app(PushNotificationService::class);

                    $service->sendSupportReply($conversation, auth()->user(), $data['body']);

                    // Push to the real user (not the system user)
                    $systemId  = config('tavan.system_user_id');
                    $userId    = $conversation->participant_one_id === $systemId
                        ? $conversation->participant_two_id
                        : $conversation->participant_one_id;

                    $push->sendToUser(
                        $userId,
                        'Tavan Podrška',
                        $data['body'],
                        ['type' => 'support', 'conversationId' => $conversation->id],
                    );

                    // Refresh the page to show the new message
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $conversation]));
                })
                ->modalHeading('Odgovor korisniku')
                ->modalSubmitActionLabel('Pošalji'),

            Action::make('toggleReplies')
                ->label(fn () => $conversation->allow_replies ? 'Onemogući odgovore' : 'Omogući odgovore')
                ->icon(fn () => $conversation->allow_replies ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                ->color(fn () => $conversation->allow_replies ? 'warning' : 'success')
                ->action(function () use ($conversation) {
                    $conversation->update(['allow_replies' => ! $conversation->allow_replies]);
                    $this->refreshFormData(['allow_replies']);
                })
                ->requiresConfirmation()
                ->modalHeading(fn () => $conversation->allow_replies
                    ? 'Onemogućiti odgovore?'
                    : 'Omogućiti odgovore?'
                )
                ->modalDescription(fn () => $conversation->allow_replies
                    ? 'Korisnik više neće moći odgovarati na ovu konverzaciju.'
                    : 'Korisnik će moći odgovarati na ovu konverzaciju.'
                ),

            Action::make('resolve')
                ->label(fn () => $conversation->status === 'resolved' ? 'Ponovo otvori' : 'Označi riješenim')
                ->icon(fn () => $conversation->status === 'resolved' ? 'heroicon-o-arrow-path' : 'heroicon-o-check-circle')
                ->color(fn () => $conversation->status === 'resolved' ? 'gray' : 'success')
                ->action(function () use ($conversation) {
                    $newStatus = $conversation->status === 'resolved' ? 'open' : 'resolved';
                    $conversation->update(['status' => $newStatus]);
                    $this->refreshFormData(['status']);
                }),
        ];
    }
}
