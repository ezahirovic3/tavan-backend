<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BrandSuggestion\StoreBrandSuggestionRequest;
use App\Models\BrandSuggestion;
use Illuminate\Http\JsonResponse;

class BrandSuggestionController extends Controller
{
    public function store(StoreBrandSuggestionRequest $request): JsonResponse
    {
        $request->user()->brandSuggestions()->create([
            'name'   => $request->validated('name'),
            'status' => 'pending',
        ]);

        return response()->json(null, 201);
    }
}
