<?php

namespace App\Filament\Resources\AdminSupportResource\Pages;

use App\Filament\Resources\AdminSupportResource;
use App\Models\Conversation;
use App\Models\User;
use App\Services\ConversationService;
use App\Services\PushNotificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ListRecords;

class ListAdminSupport extends ListRecords
{
    protected static string $resource = AdminSupportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newConversation')
                ->label('Nova konverzacija')
                ->icon('heroicon-o-plus')
                ->form([
                    Select::make('user_id')
                        ->label('Korisnik')
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search) =>
                            User::where('is_system', false)
                                ->where(fn ($q) => $q
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('username', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%")
                                )
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn ($u) => [$u->id => "{$u->name} (@{$u->username})"])
                                ->all()
                        )
                        ->getOptionLabelUsing(fn ($value) =>
                            User::find($value)?->name ?? $value
                        )
                        ->required(),
                    Textarea::make('body')
                        ->label('Poruka')
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data) {
                    $service = app(ConversationService::class);
                    $push    = app(PushNotificationService::class);

                    $conversation = $service->findOrCreateSupportConversation($data['user_id']);
                    $message      = $service->sendSupportReply($conversation, auth()->user(), $data['body']);

                    $push->sendToUser(
                        $data['user_id'],
                        'Tavan Podrška',
                        $data['body'],
                        ['type' => 'support', 'conversationId' => $conversation->id],
                    );
                })
                ->modalHeading('Pokrenite razgovor sa korisnikom')
                ->modalSubmitActionLabel('Pošalji'),
        ];
    }
}
