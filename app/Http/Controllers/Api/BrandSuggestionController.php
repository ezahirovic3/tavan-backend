<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BrandSuggestion\StoreBrandSuggestionRequest;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;

class BrandSuggestionController extends Controller
{
    public function store(StoreBrandSuggestionRequest $request): JsonResponse
    {
        $name = $request->validated('name');

        // If the suggested name is a brand we already carry, there's nothing to
        // review — don't file a pending suggestion. The mobile client already
        // attaches the listing to the matched brand in this case; this guards
        // stale brand lists and any other client.
        if (! Brand::findByNormalizedName($name)) {
            $request->user()->brandSuggestions()->create([
                'name'   => $name,
                'status' => 'pending',
            ]);
        }

        return response()->json(null, 201);
    }
}
