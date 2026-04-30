<?php

namespace App\Http\Requests\Trade;

use Illuminate\Foundation\Http\FormRequest;

class StoreTradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'         => ['required', 'ulid', 'exists:products,id'],
            'offered_product_id' => ['required', 'ulid', 'exists:products,id', 'different:product_id'],
            'message'            => ['nullable', 'string', 'max:500'],
        ];
    }
}
