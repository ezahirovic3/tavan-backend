<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UploadImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 'image' rule uses GD which doesn't support HEIC — use 'file' + mimes instead
            'image' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,heic,heif', 'max:10240'],
        ];
    }
}
