<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProductStyle;
use App\Http\Controllers\Controller;
use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserPreferenceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $prefs = UserPreference::firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'top_sizes'     => [],
                'bottom_sizes'  => [],
                'shoe_sizes'    => [],
                'categories'    => [],
                'subcategories' => [],
                'brands'        => [],
                'styles'        => [],
                'cities'        => [],
                'vintage_only'   => false,
                'designer_only'  => false,
            ]
        );

        return response()->json(['data' => $this->format($prefs)]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'top_sizes'     => ['sometimes', 'array'],
            'bottom_sizes'  => ['sometimes', 'array'],
            'shoe_sizes'    => ['sometimes', 'array'],
            'categories'    => ['sometimes', 'array'],
            'subcategories' => ['sometimes', 'array'],
            'brands'        => ['sometimes', 'array'],
            'styles'        => ['sometimes', 'array'],
            'styles.*'      => [Rule::in(ProductStyle::values())],
            'cities'        => ['sometimes', 'array'],
            'vintage_only'  => ['sometimes', 'boolean'],
            'designer_only' => ['sometimes', 'boolean'],
        ]);

        $prefs = UserPreference::updateOrCreate(
            ['user_id' => $request->user()->id],
            $data
        );

        return response()->json(['data' => $this->format($prefs)]);
    }

    private function format(UserPreference $prefs): array
    {
        return [
            'userId'        => $prefs->user_id,
            'topSizes'      => $prefs->top_sizes     ?? [],
            'bottomSizes'   => $prefs->bottom_sizes  ?? [],
            'shoeSizes'     => $prefs->shoe_sizes     ?? [],
            'categories'    => $prefs->categories    ?? [],
            'subcategories' => $prefs->subcategories ?? [],
            'brands'        => $prefs->brands        ?? [],
            'styles'        => $prefs->styles        ?? [],
            'cities'        => $prefs->cities        ?? [],
            'vintageOnly'   => (bool) ($prefs->vintage_only  ?? false),
            'designerOnly'  => (bool) ($prefs->designer_only ?? false),
        ];
    }
}
