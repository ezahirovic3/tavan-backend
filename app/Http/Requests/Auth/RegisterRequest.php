<?php

namespace App\Http\Requests\Auth;

use App\Rules\NotReservedWord;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                     => ['required', 'string', 'max:255', new NotReservedWord(exact: false)],
            'username'                 => ['required', 'string', 'max:64', 'unique:users,username', 'regex:/^[a-zA-Z0-9_.]+$/', new NotReservedWord(exact: true)],
            'email'                    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'                 => ['required', 'string', 'min:8', 'confirmed'],
            'acquired_via_campaign_id' => ['nullable', 'string', 'exists:campaigns,id'],
        ];
    }
}
