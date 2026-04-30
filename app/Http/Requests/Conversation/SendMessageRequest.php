<?php

namespace App\Http\Requests\Conversation;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'  => ['sometimes', 'in:text,image'],
            'body'  => ['required_without:image', 'nullable', 'string', 'max:2000'],
            'image' => ['required_without:body', 'nullable', 'image', 'max:10240'], // 10MB
        ];
    }
}
