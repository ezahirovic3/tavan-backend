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
            'currentPassword' => ['required', 'string'],
            'newPassword'     => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'currentPassword.required' => 'Trenutna lozinka je obavezna.',
            'newPassword.required'     => 'Nova lozinka je obavezna.',
            'newPassword.min'          => 'Nova lozinka mora imati najmanje 8 karaktera.',
            'newPassword.confirmed'    => 'Nova lozinka i potvrda se ne poklapaju.',
        ];
    }
}
