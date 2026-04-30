<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    private const CONDITION_MAP = [
        'new'       => 'novo',
        'very_good' => 'kao_novo',
        'good'      => 'odlican',
        'worn'      => 'dobar',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Normalize English condition keys → Bosnian before validation + storage
        if ($this->has('condition') && isset(self::CONDITION_MAP[$this->condition])) {
            $this->merge(['condition' => self::CONDITION_MAP[$this->condition]]);
        }
    }

    public function rules(): array
    {
        return [
            'title'         => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'price'         => ['required', 'numeric', 'min:0'],
            'root_category' => ['required', Rule::in(['women', 'men'])],
            'category'      => ['nullable', 'string', 'max:128'],
            'subcategory'   => ['nullable', 'string', 'max:128'],
            'condition'     => ['required', Rule::in(['novo', 'kao_novo', 'odlican', 'dobar', 'zadrzavajuci'])],
            'size'          => ['nullable', 'string', 'max:32'],
            'color'         => ['nullable', 'string', 'max:64'],
            'material'      => ['nullable', 'string', 'max:128'],
            'shipping_size' => ['required', Rule::in(['S', 'M', 'L'])],
            'location'      => ['required', 'string', 'max:128'],
            'brand_id'      => ['nullable', 'ulid', 'exists:brands,id'],
            'brand_custom'  => ['nullable', 'string', 'max:255'],
            'allows_trades' => ['boolean'],
            'allows_offers' => ['boolean'],
            'measurements'  => ['nullable', 'array'],
        ];
    }
}
