<?php

namespace App\Http\Requests\Offer;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'    => ['required', 'ulid', 'exists:products,id'],
            'offered_price' => ['required', 'numeric', 'min:0.01'],
            'message'       => ['nullable', 'string', 'max:500'],
        ];
    }
}
