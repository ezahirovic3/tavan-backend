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
            'email'        => ['required', 'string', 'email'],
            'reset_token'  => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'         => 'Email adresa je obavezna.',
            'reset_token.required'   => 'Token za reset je obavezan.',
            'new_password.required'  => 'Nova lozinka je obavezna.',
            'new_password.min'       => 'Nova lozinka mora imati najmanje 8 karaktera.',
            'new_password.confirmed' => 'Lozinka i potvrda se ne poklapaju.',
        ];
    }
}
