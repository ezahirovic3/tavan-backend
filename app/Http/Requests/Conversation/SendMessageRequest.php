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
            'type'       => ['sometimes', 'in:text,image,system_inquiry'],
            'body'       => ['required_without:image,product_id', 'nullable', 'string', 'max:2000'],
            'image'      => ['required_without:body,product_id', 'nullable', 'image', 'max:10240'], // 10MB
            'product_id' => ['required_if:type,system_inquiry', 'nullable', 'string'],
            'text'       => ['required_if:type,system_inquiry', 'nullable', 'string', 'max:2000'],
        ];
    }
}
