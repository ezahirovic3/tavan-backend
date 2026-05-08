<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserBlockController extends Controller
{
    /** GET /users/me/blocks — list users I have blocked */
    public function index(Request $request): JsonResponse
    {
        $blocked = User::whereIn('id',
            UserBlock::where('blocker_id', $request->user()->id)->pluck('blocked_id')
        )->get();

        return response()->json(['data' => UserResource::collection($blocked)]);
    }

    /** POST /users/{user}/block */
    public function store(Request $request, User $user): JsonResponse
    {
        abort_if($request->user()->id === $user->id, 422, 'Ne možeš blokirati samog sebe.');

        UserBlock::firstOrCreate([
            'blocker_id' => $request->user()->id,
            'blocked_id' => $user->id,
        ]);

        return response()->json(['message' => 'Korisnik je blokiran.']);
    }

    /** DELETE /users/{user}/block */
    public function destroy(Request $request, User $user): JsonResponse
    {
        UserBlock::where('blocker_id', $request->user()->id)
            ->where('blocked_id', $user->id)
            ->delete();

        return response()->json(['message' => 'Blokiranje je uklonjenо.']);
    }
}
