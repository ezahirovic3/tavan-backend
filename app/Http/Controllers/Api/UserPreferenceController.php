<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
                'cities'        => [],
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
            'cities'        => ['sometimes', 'array'],
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
            'userId'       => $prefs->user_id,
            'topSizes'     => $prefs->top_sizes     ?? [],
            'bottomSizes'  => $prefs->bottom_sizes  ?? [],
            'shoeSizes'    => $prefs->shoe_sizes     ?? [],
            'categories'   => $prefs->categories    ?? [],
            'subcategories'=> $prefs->subcategories ?? [],
            'cities'       => $prefs->cities        ?? [],
        ];
    }
}
