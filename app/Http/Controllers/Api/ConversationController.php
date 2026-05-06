<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Conversation\SendMessageRequest;
use App\Http\Requests\Conversation\StartConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Services\ConversationService;
use App\Services\ImageService;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversations,
        private readonly PushNotificationService $push,
        private readonly ImageService $images,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $conversations = Conversation::where('participant_one_id', $userId)
            ->orWhere('participant_two_id', $userId)
            ->with(['participantOne', 'participantTwo', 'lastMessage.sender', 'product.images'])
            ->orderByDesc('last_message_at')
            ->paginate(30);

        // Attach unread counts
        $conversations->each(function ($c) use ($userId) {
            $c->unread_count = $this->conversations->unreadCount($c, $userId);
        });

        return response()->json([
            'data' => ConversationResource::collection($conversations->items()),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page'    => $conversations->lastPage(),
                'per_page'     => $conversations->perPage(),
                'total'        => $conversations->total(),
            ],
        ]);
    }

    public function store(StartConversationRequest $request): JsonResponse
    {
        $conversation = $this->conversations->findOrCreate(
            $request->user()->id,
            $request->user_id,
            $request->product_id
        );

        $conversation->load(['participantOne', 'participantTwo', 'lastMessage.sender', 'product.images']);
        $conversation->unread_count = $this->conversations->unreadCount($conversation, $request->user()->id);

        return response()->json(['data' => new ConversationResource($conversation)], 201);
    }

    /**
     * GET /conversations/{id}/info — returns the conversation object (not messages).
     * Used by the mobile app to populate the chat header (otherUser, product).
     */
    public function info(Request $request, Conversation $conversation): JsonResponse
    {
        abort_if(! $conversation->hasParticipant($request->user()->id), 403);

        $conversation->load(['participantOne', 'participantTwo', 'lastMessage.sender', 'product.images']);
        $conversation->unread_count = $this->conversations->unreadCount($conversation, $request->user()->id);

        return response()->json(['data' => new ConversationResource($conversation)]);
    }

    /**
     * GET /conversations/{id} — returns paginated messages for the conversation.
     */
    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        abort_if(! $conversation->hasParticipant($request->user()->id), 403);

        $messages = $conversation->messages()
            ->with('sender')
            ->latest('created_at')
            ->paginate(50);

        // Mark messages from other user as read
        $this->conversations->markRead($conversation, $request->user()->id);

        return response()->json([
            'data' => MessageResource::collection($messages->items()),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
                'per_page'     => $messages->perPage(),
                'total'        => $messages->total(),
            ],
        ]);
    }

    public function sendMessage(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        abort_if(! $conversation->hasParticipant($request->user()->id), 403);

        $recipientId = $conversation->participant_one_id === $request->user()->id
            ? $conversation->participant_two_id
            : $conversation->participant_one_id;

        // Image message
        if ($request->hasFile('image')) {
            $url     = $this->images->uploadMessageImage($request->file('image'));
            $message = $this->conversations->sendImage($conversation, $request->user(), $url);

            $this->push->sendToUser(
                $recipientId,
                $request->user()->name,
                '📷 Slika',
                ['type' => 'message', 'conversationId' => $conversation->id],
            );

            return response()->json(['data' => new MessageResource($message)], 201);
        }

        // Inquiry message (system_inquiry)
        if ($request->type === 'system_inquiry') {
            $message = $this->conversations->sendSystemMessage(
                $conversation,
                $request->user()->id,
                'system_inquiry',
                ['productId' => $request->product_id, 'text' => $request->text],
            );

            $this->push->sendToUser(
                $recipientId,
                $request->user()->name,
                '❓ ' . $request->text,
                ['type' => 'message', 'conversationId' => $conversation->id],
            );

            return response()->json(['data' => new MessageResource($message)], 201);
        }

        // Text message
        $message = $this->conversations->sendText($conversation, $request->user(), $request->body);

        $this->push->sendToUser(
            $recipientId,
            $request->user()->name,
            $request->body,
            ['type' => 'message', 'conversationId' => $conversation->id],
        );

        return response()->json(['data' => new MessageResource($message)], 201);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $count = \App\Models\Message::whereHas('conversation', function ($q) use ($userId) {
            $q->where('participant_one_id', $userId)
              ->orWhere('participant_two_id', $userId);
        })
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();

        return response()->json(['data' => ['count' => $count]]);
    }

    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        abort_if(! $conversation->hasParticipant($request->user()->id), 403);

        $this->conversations->markRead($conversation, $request->user()->id);

        return response()->json(['message' => 'Poruke označene kao pročitane.']);
    }
}
