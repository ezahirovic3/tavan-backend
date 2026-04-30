<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'street'     => ['required', 'string', 'max:255'],
            'city'       => ['required', 'string', 'max:128'],
            'phone'      => ['required', 'string', 'max:32'],
            'is_default' => ['boolean'],
        ];
    }
}
