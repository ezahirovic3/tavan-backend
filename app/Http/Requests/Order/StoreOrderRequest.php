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
            // Single-item orders send product_id (legacy mobile);
            // bundles send product_ids. Exactly one of the two is required.
            'product_id'      => ['required_without:product_ids', 'nullable', 'ulid', 'exists:products,id'],
            'product_ids'     => ['sometimes', 'array', 'min:1', 'max:20'],
            'product_ids.*'   => ['ulid', 'distinct', 'exists:products,id'],
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
