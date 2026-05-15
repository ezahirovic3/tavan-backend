<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushToken;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PushTokenController extends Controller
{
    public function __construct(private PushNotificationService $push) {}

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
     * Reset the push badge counter to 0 (called when the app is opened/foregrounded).
     */
    public function resetBadge(Request $request): JsonResponse
    {
        $this->push->resetBadge($request->user()->id);
        return response()->json(['data' => ['badge' => 0]]);
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
