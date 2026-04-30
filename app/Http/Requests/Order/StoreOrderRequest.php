<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'      => ['required', 'ulid', 'exists:products,id'],
            'offer_id'        => ['sometimes', 'nullable', 'ulid', 'exists:offers,id'],
            'payment_method'  => ['required', 'string', 'max:64'],
            'delivery_method' => ['required', 'string', 'max:64'],
            'shipping_name'   => ['sometimes', 'nullable', 'string', 'max:255'],
            'shipping_street' => ['sometimes', 'nullable', 'string', 'max:255'],
            'shipping_city'   => ['sometimes', 'nullable', 'string', 'max:128'],
            'shipping_phone'  => ['sometimes', 'nullable', 'string', 'max:32'],
        ];
    }
}
