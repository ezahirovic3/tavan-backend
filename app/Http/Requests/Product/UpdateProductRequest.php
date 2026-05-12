<?php

namespace App\Http\Requests\Product;

use App\Models\Brand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // If brand_id is explicitly sent as null, resolve to the "Ostali" brand
        if ($this->has('brand_id') && ! $this->filled('brand_id')) {
            $other = Brand::where('is_other', true)->value('id');
            if ($other) {
                $this->merge(['brand_id' => $other]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'title'         => ['sometimes', 'string', 'max:255'],
            'description'   => ['sometimes', 'nullable', 'string'],
            'price'         => ['sometimes', 'numeric', 'min:0'],
            'root_category' => ['sometimes', Rule::in(['women', 'men'])],
            'category'      => ['sometimes', 'nullable', 'string', 'max:128'],
            'subcategory'   => ['sometimes', 'nullable', 'string', 'max:128'],
            'condition'     => ['sometimes', Rule::in(['new', 'very_good', 'good', 'worn'])],
            'size'          => ['sometimes', 'nullable', 'string', 'max:32'],
            'color'         => ['sometimes', 'nullable', 'string', 'max:64'],
            'material'      => ['sometimes', 'nullable', 'string', 'max:128'],
            'shipping_size' => ['sometimes', Rule::in(['S', 'M', 'L'])],
            'location'      => ['sometimes', 'string', 'max:128'],
            'brand_id'      => ['sometimes', 'nullable', 'ulid', 'exists:brands,id'],
            'allows_trades'        => ['sometimes', 'boolean'],
            'allows_offers'        => ['sometimes', 'boolean'],
            'pickup_enabled'       => ['sometimes', 'nullable', 'boolean'],
            'free_shipping'        => ['sometimes', 'nullable', 'boolean'],
            'exact_shipping_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'measurements'  => ['sometimes', 'nullable', 'array'],
            'status'        => ['sometimes', Rule::in(['draft', 'pending_review', 'active', 'reserved', 'sold'])],
        ];
    }
}
