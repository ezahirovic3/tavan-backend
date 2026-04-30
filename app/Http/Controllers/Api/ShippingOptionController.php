<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShippingOptionResource;
use App\Models\ShippingOption;
use Illuminate\Http\JsonResponse;

class ShippingOptionController extends Controller
{
    public function index(): JsonResponse
    {
        $options = ShippingOption::active()->get();

        return response()->json(['data' => ShippingOptionResource::collection($options)]);
    }
}
