<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyPhoneOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                'regex:/^\+[1-9]\d{6,14}$/',
                Rule::unique('users', 'phone')->ignore($this->user()?->id),
            ],
            'otp'   => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex'  => 'Unesite ispravan broj telefona u međunarodnom formatu (npr. +38761123456).',
            'phone.unique' => 'Ovaj broj telefona je već registrovan na drugom računu.',
        ];
    }
}
