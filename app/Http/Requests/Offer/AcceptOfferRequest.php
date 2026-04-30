<?php

namespace App\Http\Requests\Offer;

use Illuminate\Foundation\Http\FormRequest;

class AcceptOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Offer acceptance is a simple status update — no shipping details required here.
        // The order (with shipping) is created separately when the buyer checks out.
        return [];
    }
}
