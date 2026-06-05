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
                'max:32',
                Rule::unique('users', 'phone')->ignore($this->user()?->id),
            ],
            'otp'   => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'Ovaj broj telefona je već registrovan na drugom računu.',
        ];
    }
}
