<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\NormalizesPhone;
use Illuminate\Foundation\Http\FormRequest;

class SendPhoneOtpRequest extends FormRequest
{
    use NormalizesPhone;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{6,14}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Unesite ispravan broj telefona u međunarodnom formatu (npr. +38761123456).',
        ];
    }
}
