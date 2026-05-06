<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'order_number'    => $this->order_number,
            'status'          => $this->status,
            'subtotal'        => (float) $this->subtotal,
            'discount'        => (float) $this->discount,
            'shipping_cost'   => (float) $this->shipping_cost,
            'total'           => (float) $this->total,
            'payment_method'  => $this->payment_method,
            'delivery_method' => $this->delivery_method,
            'shipping'        => [
                'name'   => $this->shipping_name,
                'street' => $this->shipping_street,
                'city'   => $this->shipping_city,
                'phone'  => $this->shipping_phone,
            ],
            'product'         => new ProductResource($this->whenLoaded('product')),
            'buyer'           => new UserResource($this->whenLoaded('buyer')),
            'seller'          => new UserResource($this->whenLoaded('seller')),
            'offer'           => new OfferResource($this->whenLoaded('offer')),
            'reviews'         => ReviewResource::collection($this->whenLoaded('reviews')),
            'created_at'      => $this->created_at?->toISOString(),
        ];
    }
}
