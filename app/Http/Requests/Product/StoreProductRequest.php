<?php

namespace App\Http\Requests\Product;

use App\Models\Brand;
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
        if ($this->has('condition') && isset(self::CONDITION_MAP[$this->condition])) {
            $this->merge(['condition' => self::CONDITION_MAP[$this->condition]]);
        }

        if (! $this->filled('brand_id')) {
            $other = Brand::where('is_other', true)->value('id');
            if ($other) {
                $this->merge(['brand_id' => $other]);
            }
        }
    }

    public function rules(): array
    {
        $isDraft = $this->input('status') === 'draft';

        return [
            'title'         => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'price'         => [$isDraft ? 'nullable' : 'required', 'numeric', 'min:0'],
            'root_category' => [$isDraft ? 'nullable' : 'required', Rule::in(['women', 'men'])],
            'category'      => ['nullable', 'string', 'max:128'],
            'subcategory'   => ['nullable', 'string', 'max:128'],
            'condition'     => [$isDraft ? 'nullable' : 'required', 'nullable', Rule::in(['novo', 'kao_novo', 'odlican', 'dobar', 'zadrzavajuci'])],
            'size'          => ['nullable', 'string', 'max:32'],
            'color'         => ['nullable', 'string', 'max:64'],
            'material'      => ['nullable', 'string', 'max:128'],
            'shipping_size' => [$isDraft ? 'nullable' : 'required', 'nullable', Rule::in(['S', 'M', 'L'])],
            'location'      => [$isDraft ? 'nullable' : 'required', 'nullable', 'string', 'max:128'],
            'brand_id'      => ['nullable', 'ulid', 'exists:brands,id'],
            'allows_trades' => ['boolean'],
            'allows_offers' => ['boolean'],
            'measurements'  => ['nullable', 'array'],
            'status'        => ['nullable', Rule::in(['draft', 'active'])],
        ];
    }
}
