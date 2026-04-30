<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
        if ($this->has('condition') && isset(self::CONDITION_MAP[$this->condition])) {
            $this->merge(['condition' => self::CONDITION_MAP[$this->condition]]);
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
            'condition'     => ['sometimes', Rule::in(['novo', 'kao_novo', 'odlican', 'dobar', 'zadrzavajuci'])],
            'size'          => ['sometimes', 'nullable', 'string', 'max:32'],
            'color'         => ['sometimes', 'nullable', 'string', 'max:64'],
            'material'      => ['sometimes', 'nullable', 'string', 'max:128'],
            'shipping_size' => ['sometimes', Rule::in(['S', 'M', 'L'])],
            'location'      => ['sometimes', 'string', 'max:128'],
            'brand_id'      => ['sometimes', 'nullable', 'ulid', 'exists:brands,id'],
            'brand_custom'  => ['sometimes', 'nullable', 'string', 'max:255'],
            'allows_trades' => ['sometimes', 'boolean'],
            'allows_offers' => ['sometimes', 'boolean'],
            'measurements'  => ['sometimes', 'nullable', 'array'],
        ];
    }
}
