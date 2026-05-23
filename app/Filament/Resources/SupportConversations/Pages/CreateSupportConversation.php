<?php

namespace App\Filament\Resources\SupportConversations\Pages;

use App\Filament\Resources\SupportConversations\SupportConversationResource;
use App\Models\Conversation;
use App\Models\Message;
use Filament\Resources\Pages\CreateRecord;

class CreateSupportConversation extends CreateRecord
{
    protected static string $resource = SupportConversationResource::class;

    protected function handleRecord(array $data): Conversation
    {
        $convo = Conversation::firstOrCreate(
            [
                'participant_one_id' => $data['participant_one_id'],
                'participant_two_id' => config('tavan.system_user_id'),
            ],
            [
                'allow_replies'   => $data['allow_replies'] ?? true,
                'status'          => 'open',
                'type'            => 'admin_support',
                'last_message_at' => now(),
            ]
        );

        if (!$convo->wasRecentlyCreated && $convo->status === 'closed') {
            $convo->update(['status' => 'open', 'allow_replies' => true]);
        }

        Message::create([
            'conversation_id' => $convo->id,
            'sender_id'       => auth()->id(),
            'body'            => $data['initial_message'],
        ]);

        return $convo;
    }

    protected function handleRecordCreation(array $data): Conversation
    {
        return $this->handleRecord($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
