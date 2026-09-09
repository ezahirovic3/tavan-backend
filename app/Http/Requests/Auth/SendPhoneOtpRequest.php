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
            'phone' => self::bosnianPhoneRules(),
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Trenutno podržavamo samo brojeve mobitela iz BiH (+387 6X XXX XXX).',
        ];
    }
}
