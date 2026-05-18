<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'       => ['required', 'string', 'email'],
            'resetToken'  => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'        => 'Email adresa je obavezna.',
            'resetToken.required'   => 'Token za reset je obavezan.',
            'newPassword.required'  => 'Nova lozinka je obavezna.',
            'newPassword.min'       => 'Nova lozinka mora imati najmanje 8 karaktera.',
            'newPassword.confirmed' => 'Lozinka i potvrda se ne poklapaju.',
        ];
    }
}
