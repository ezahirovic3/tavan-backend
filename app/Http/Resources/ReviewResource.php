<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'reviewer_id' => $this->reviewer_id,
            'reviewed_id' => $this->reviewed_id,
            'rating'      => $this->rating,
            'comment'     => $this->comment,
            'role'        => $this->role,
            'reviewer'    => new UserResource($this->whenLoaded('reviewer')),
            'reviewed'    => new UserResource($this->whenLoaded('reviewed')),
            'created_at'  => $this->created_at->toISOString(),
        ];
    }
}
