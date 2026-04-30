<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'product_id'    => $this->product_id,
            'offered_price' => (float) $this->offered_price,
            'counter_price' => $this->counter_price !== null ? (float) $this->counter_price : null,
            'message'       => $this->message,
            'status'        => $this->status,
            'expires_at'    => $this->expires_at?->toISOString(),
            'product'       => new ProductResource($this->whenLoaded('product')),
            'buyer'         => new UserResource($this->whenLoaded('buyer')),
            'seller'        => new UserResource($this->whenLoaded('seller')),
            'created_at'    => $this->created_at->toISOString(),
        ];
    }
}
