<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'type'            => $this->type,
            'body'            => $this->body,
            'payload'         => $this->payload,
            'read_at'         => $this->read_at?->toISOString(),
            'sender_id'       => $this->sender_id,
            'sender'          => new UserResource($this->whenLoaded('sender')),
            'created_at'      => $this->created_at?->toISOString(),
        ];
    }
}
