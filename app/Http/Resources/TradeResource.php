<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TradeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'product_id'          => $this->product_id,
            'offered_product_id'  => $this->offered_product_id,
            'message'             => $this->message,
            'status'              => $this->status,
            'product'             => new ProductResource($this->whenLoaded('product')),
            'offered_product'     => new ProductResource($this->whenLoaded('offeredProduct')),
            'buyer'               => new UserResource($this->whenLoaded('buyer')),
            'seller'              => new UserResource($this->whenLoaded('seller')),
            'created_at'          => $this->created_at?->toISOString(),
        ];
    }
}
