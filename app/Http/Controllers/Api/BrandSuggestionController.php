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
        if (! $this->brandAlreadyExists($name)) {
            $request->user()->brandSuggestions()->create([
                'name'   => $name,
                'status' => 'pending',
            ]);
        }

        return response()->json(null, 201);
    }

    private function brandAlreadyExists(string $name): bool
    {
        $target = $this->normalize($name);

        if ($target === '') {
            return false;
        }

        // Same scope the mobile brand list uses (active, non-"Ostali"), so the
        // client and server agree on what counts as an existing brand.
        return Brand::query()
            ->where('is_active', true)
            ->where('is_other', false)
            ->get(['name'])
            ->contains(fn (Brand $brand) => $this->normalize($brand->name) === $target);
    }

    /**
     * Fold case, apostrophe variants, dots/dashes and repeated whitespace so
     * "isabel-marant" / "Isabel  Marant" resolve to the same brand.
     * Mirrors normalizeBrandName() in the mobile brandDesigner screen.
     */
    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(["'", '’', '`', '.', '-'], '', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }
}
