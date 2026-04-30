<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'size'        => $this->size,
            'label'       => $this->label,
            'price'       => $this->price,
            'description' => $this->description,
        ];
    }
}
