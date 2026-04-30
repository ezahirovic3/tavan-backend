<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

// Mobile app subscribes to its own user channel for push/notification events
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return $user->id === $id;
});

// Private conversation channel — only participants can subscribe
Broadcast::channel('conversation.{conversationId}', function ($user, string $conversationId) {
    $conversation = Conversation::find($conversationId);

    return $conversation && $conversation->hasParticipant($user->id);
});
