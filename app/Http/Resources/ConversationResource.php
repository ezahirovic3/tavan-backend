<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;
        $systemId = config('tavan.system_user_id');

        // For admin_support conversations always show the system user as other_user,
        // regardless of which participant slot it occupies.
        if ($this->type === 'admin_support') {
            $otherUser = ($this->participant_one_id === $systemId)
                ? $this->participantOne
                : $this->participantTwo;
        } else {
            $otherUser = ($userId === $this->participant_one_id)
                ? $this->participantTwo
                : $this->participantOne;
        }

        return [
            'id'            => $this->id,
            'type'          => $this->type,
            'allow_replies' => $this->allow_replies,
            'status'        => $this->status,
            'other_user'    => new UserResource($otherUser),
            'product'       => new ProductResource($this->whenLoaded('product')),
            'last_message'  => new MessageResource($this->whenLoaded('lastMessage')),
            'unread_count'  => $this->when(isset($this->unread_count), fn () => $this->unread_count),
            'last_message_at' => $this->last_message_at?->toISOString(),
            'created_at'    => $this->created_at?->toISOString(),
        ];
    }
}
