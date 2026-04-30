<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;

        return [
            'id'              => $this->id,
            'other_user'      => new UserResource(
                $userId === $this->participant_one_id
                    ? $this->participantTwo
                    : $this->participantOne
            ),
            'product'         => new ProductResource($this->whenLoaded('product')),
            'last_message'    => new MessageResource($this->whenLoaded('lastMessage')),
            'unread_count'    => $this->when(isset($this->unread_count), fn () => $this->unread_count),
            'last_message_at' => $this->last_message_at?->toISOString(),
            'created_at'      => $this->created_at->toISOString(),
        ];
    }
}
