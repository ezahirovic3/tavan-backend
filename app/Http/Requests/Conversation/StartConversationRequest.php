<?php

namespace App\Http\Requests\Conversation;

use Illuminate\Foundation\Http\FormRequest;

class StartConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'    => ['required', 'ulid', 'exists:users,id', 'different:' . $this->user()->id],
            'product_id' => ['nullable', 'ulid', 'exists:products,id'],
        ];
    }
}
