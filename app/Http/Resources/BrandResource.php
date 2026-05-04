<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'slug'       => $this->slug,
            'logo_url'   => $this->logo_url ? Storage::disk('r2')->url($this->logo_url) : null,
            'sort_order' => $this->sort_order,
        ];
    }
}
