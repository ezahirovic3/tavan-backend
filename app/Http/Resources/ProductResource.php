<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'description'   => $this->description,
            'price'         => (float) $this->price,
            'status'        => $this->status,
            'condition'     => $this->condition,
            'size'          => $this->size,
            'color'         => $this->color,
            'material'      => $this->material,
            'root_category' => $this->root_category,
            'category'      => $this->category,
            'subcategory'   => $this->subcategory,
            'shipping_size' => $this->shipping_size,
            'location'      => $this->location,
            'allows_trades' => $this->allows_trades,
            'allows_offers' => $this->allows_offers,
            'likes'         => $this->likes,
            'measurements'  => $this->measurements,
            'brand'         => new BrandResource($this->whenLoaded('brand')),
            'images'        => ProductImageResource::collection($this->whenLoaded('images')),
            'seller'        => new UserResource($this->whenLoaded('seller')),
            'is_wishlisted' => $this->when(
                isset($this->is_wishlisted),
                fn () => (bool) $this->is_wishlisted
            ),
            'created_at'    => $this->created_at?->toISOString(),
        ];
    }
}
