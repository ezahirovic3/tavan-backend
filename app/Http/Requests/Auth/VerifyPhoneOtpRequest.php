<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\NormalizesPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyPhoneOtpRequest extends FormRequest
{
    use NormalizesPhone;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => [
                ...self::bosnianPhoneRules(),
                Rule::unique('users', 'phone')->ignore($this->user()?->id),
            ],
            'otp'   => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex'  => 'Trenutno podržavamo samo brojeve mobitela iz BiH (+387 6X XXX XXX).',
            'phone.unique' => 'Ovaj broj telefona je već registrovan na drugom računu.',
        ];
    }
}
