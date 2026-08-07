<?php

namespace App\Services;

use App\Events\MessagesRead;
use App\Events\NewMessage;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

class ConversationService
{
    /**
     * Find an existing conversation between two users or create one.
     * participant_one_id is always the lexicographically lower ULID.
     */
    public function findOrCreate(string $userId1, string $userId2, ?string $productId = null): Conversation
    {
        [$one, $two] = collect([$userId1, $userId2])->sort()->values()->all();

        return Conversation::firstOrCreate(
            ['participant_one_id' => $one, 'participant_two_id' => $two],
            ['product_id' => $productId, 'type' => 'user']
        );
    }

    /**
     * Find or create the admin_support conversation between the system user and a real user.
     */
    public function findOrCreateSupportConversation(string $userId): Conversation
    {
        return Conversation::firstOrCreate(
            [
                'participant_one_id' => $userId,
                'participant_two_id' => config('tavan.system_user_id'),
            ],
            ['type' => 'admin_support', 'allow_replies' => true, 'status' => 'open', 'last_message_at' => now()]
        );
    }

    /**
     * Send a message from the admin side of a support conversation.
     * The message is stored with sender_id = system user so mobile shows "Tavan Podrška".
     * The real admin's ID is preserved in the payload for audit purposes.
     *
     * Semantics: "open" means a real user is waiting on us; "resolved" means nothing is
     * pending on our side. So once the admin/system sends a message, the ball is back in
     * the user's court — the conversation is auto-resolved. It reopens automatically the
     * moment the user replies (see sendText/sendImage below).
     */
    public function sendSupportReply(Conversation $conversation, User $adminUser, string $body): Message
    {
        $message = $this->createMessage(
            $conversation,
            config('tavan.system_user_id'),
            'text',
            $body,
            ['admin_id' => $adminUser->id, 'admin_name' => $adminUser->name],
        );

        if ($conversation->status !== 'resolved') {
            $conversation->update(['status' => 'resolved']);
        }

        broadcast(new NewMessage($message))->toOthers();

        return $message;
    }

    public function sendText(Conversation $conversation, User $sender, string $body): Message
    {
        $message = $this->createMessage($conversation, $sender->id, 'text', $body, null);

        $this->reopenIfUserReplied($conversation, $sender);

        broadcast(new NewMessage($message))->toOthers();

        return $message;
    }

    public function sendImage(Conversation $conversation, User $sender, string $imageUrl): Message
    {
        $message = $this->createMessage($conversation, $sender->id, 'image', $imageUrl, null);

        $this->reopenIfUserReplied($conversation, $sender);

        broadcast(new NewMessage($message))->toOthers();

        return $message;
    }

    /**
     * A real user (not the system/admin user) messaging into a support conversation always
     * means it needs admin attention again — reopen it even if it was previously resolved.
     */
    private function reopenIfUserReplied(Conversation $conversation, User $sender): void
    {
        if ($conversation->isAdminSupport()
            && $sender->id !== config('tavan.system_user_id')
            && $conversation->status !== 'open') {
            $conversation->update(['status' => 'open']);
        }
    }

    public function sendSystemMessage(
        Conversation $conversation,
        string $senderId,
        string $type,
        array $payload,
        ?string $body = null
    ): Message {
        $message = $this->createMessage($conversation, $senderId, $type, $body, $payload);

        broadcast(new NewMessage($message));

        return $message;
    }

    public function markRead(Conversation $conversation, string $userId): void
    {
        $affected = $conversation->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($affected > 0) {
            broadcast(new MessagesRead($conversation->id, $userId));
        }
    }

    public function unreadCount(Conversation $conversation, string $userId): int
    {
        return $conversation->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();
    }

    private function createMessage(
        Conversation $conversation,
        string $senderId,
        string $type,
        ?string $body,
        ?array $payload
    ): Message {
        $message = $conversation->messages()->create([
            'sender_id'  => $senderId,
            'type'       => $type,
            'body'       => $body,
            'payload'    => $payload,
            'created_at' => now(),
        ]);

        $conversation->update(['last_message_at' => now()]);

        return $message->load('sender');
    }
}
