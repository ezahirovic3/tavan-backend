<?php

namespace App\Http\Requests\BrandSuggestion;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
