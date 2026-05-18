<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyResetOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'otp'   => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email adresa je obavezna.',
            'otp.required'   => 'Verifikacijski kod je obavezan.',
            'otp.size'       => 'Verifikacijski kod mora imati 6 cifara.',
        ];
    }
}
