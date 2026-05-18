<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Trenutna lozinka je obavezna.',
            'new_password.required'     => 'Nova lozinka je obavezna.',
            'new_password.min'          => 'Nova lozinka mora imati najmanje 8 karaktera.',
            'new_password.confirmed'    => 'Nova lozinka i potvrda se ne poklapaju.',
        ];
    }
}
