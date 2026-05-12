<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'label'      => $this->label,
            'name'       => $this->name,
            'street'     => $this->street,
            'city'       => $this->city,
            'postcode'   => $this->postcode,
            'phone'      => $this->phone,
            'is_default' => $this->is_default,
        ];
    }
}
