<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['sometimes', 'string', 'max:255'],
            'username' => ['sometimes', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_.]+$/', Rule::unique('users')->ignore($this->user()->id)],
            'bio'      => ['sometimes', 'nullable', 'string', 'max:500'],
            'location' => ['sometimes', 'nullable', 'string', 'max:128'],
        ];
    }
}
