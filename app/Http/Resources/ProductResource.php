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
            'shipping_size'        => $this->shipping_size,
            'location'             => $this->location,
            'pickup_enabled'       => $this->pickup_enabled,
            'free_shipping'        => $this->free_shipping,
            'exact_shipping_price' => $this->exact_shipping_price !== null ? (float) $this->exact_shipping_price : null,
            'allows_trades'        => $this->allows_trades,
            'allows_offers'        => $this->allows_offers,
            'likes'         => $this->likes,
            'view_count'    => (int) $this->view_count,
            'measurements'  => $this->measurements,
            'vintage'       => $this->vintage_status === 'approved' ? [
                'era'         => $this->vintage_era,
                'notes'       => $this->vintage_notes,
                'provenance'  => $this->vintage_provenance,
            ] : null,
            'vintage_status'        => $this->vintage_status,
            'vintage_reject_reason' => $this->vintage_reject_reason,
            'designer'              => $this->designer_status === 'approved' ? [
                'brand' => $this->designer_brand,
                'notes' => $this->designer_notes,
            ] : null,
            'designer_status'        => $this->designer_status,
            'designer_reject_reason' => $this->designer_reject_reason,
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
