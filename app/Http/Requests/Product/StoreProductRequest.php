<?php

namespace App\Http\Requests\Product;

use App\Models\Brand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    private const LEGACY_CONDITION_MAP = [
        'novo'         => 'new',
        'kao_novo'     => 'very_good',
        'odlican'      => 'good',
        'dobar'        => 'worn',
        'zadrzavajuci' => 'worn',
    ];

    protected function prepareForValidation(): void
    {
        if ($this->has('condition') && isset(self::LEGACY_CONDITION_MAP[$this->condition])) {
            $this->merge(['condition' => self::LEGACY_CONDITION_MAP[$this->condition]]);
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
            'condition'     => [$isDraft ? 'nullable' : 'required', 'nullable', Rule::in(['new', 'very_good', 'good', 'worn'])],
            'size'          => ['nullable', 'string', 'max:32'],
            'color'         => ['nullable', 'string', 'max:64'],
            'material'      => ['nullable', 'string', 'max:128'],
            'shipping_size' => ['nullable', Rule::in(['S', 'M', 'L'])],
            'location'      => [$isDraft ? 'nullable' : 'required', 'nullable', 'string', 'max:128'],
            'brand_id'      => ['nullable', 'ulid', 'exists:brands,id'],
            'allows_trades'        => ['boolean'],
            'allows_offers'        => ['boolean'],
            'pickup_enabled'       => ['nullable', 'boolean'],
            'free_shipping'        => ['nullable', 'boolean'],
            'exact_shipping_price' => ['nullable', 'numeric', 'min:0'],
            'measurements'  => ['nullable', 'array'],
            // Client may only signal intent to save as draft.
            // The server decides the actual status (pending_review vs active) for published listings.
            'status'        => ['nullable', Rule::in(['draft'])],
        ];
    }
}
