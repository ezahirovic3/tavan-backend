<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PushTokenController extends Controller
{
    /**
     * Register (or update) an Expo push token for the authenticated user.
     * Safe to call every time the app launches — uses upsert under the hood.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'    => ['required', 'string', 'max:512'],
            'platform' => ['required', Rule::in(['ios', 'android'])],
        ]);

        PushToken::updateOrCreate(
            ['user_id' => $request->user()->id, 'token' => $data['token']],
            ['platform' => $data['platform']]
        );

        return response()->json(['message' => 'Token registrovan.'], 200);
    }

    /**
     * Unregister a push token (called on logout).
     */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
        ]);

        PushToken::where('user_id', $request->user()->id)
            ->where('token', $data['token'])
            ->delete();

        return response()->json(['message' => 'Token uklonjen.'], 200);
    }
}
