<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'key'        => $this->key,
            'icon'       => $this->icon,
            'sort_order' => $this->sort_order,
            'children'   => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
