<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Follow;
use App\Models\User;
use App\Models\UserBlock;
use App\Services\PushNotificationService;
use App\Services\UserNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function __construct(
        private readonly PushNotificationService $push,
        private readonly UserNotificationService $notifications,
    ) {}

    /** POST /users/{user}/follow */
    public function store(Request $request, User $user): JsonResponse
    {
        abort_if($request->user()->id === $user->id, 422, 'Ne možeš pratiti samog sebe.');

        $blocked = UserBlock::where(function ($q) use ($request, $user) {
            $q->where('blocker_id', $request->user()->id)->where('blocked_id', $user->id);
        })->orWhere(function ($q) use ($request, $user) {
            $q->where('blocker_id', $user->id)->where('blocked_id', $request->user()->id);
        })->exists();

        abort_if($blocked, 422, 'Ne možeš pratiti ovog korisnika.');

        $follower = $request->user();
        $created  = Follow::firstOrCreate([
            'follower_id' => $follower->id,
            'followed_id' => $user->id,
        ]);

        // firstOrCreate's wasRecentlyCreated is false on the idempotent
        // re-POST case — only notify on an actual new follow.
        if ($created->wasRecentlyCreated) {
            $title = 'Novi pratitelj 👋';
            $body  = $follower->name . ' te sada prati.';
            $meta  = ['followerId' => $follower->id];

            $this->push->sendToUser($user->id, $title, $body, [
                'type' => 'new_follower',
                ...$meta,
            ]);

            $this->notifications->record($user, 'new_follower', $title, $body, $meta);
        }

        return response()->json(['message' => 'Sada pratiš ovog korisnika.']);
    }

    /** DELETE /users/{user}/follow */
    public function destroy(Request $request, User $user): JsonResponse
    {
        Follow::where('follower_id', $request->user()->id)
            ->where('followed_id', $user->id)
            ->delete();

        return response()->json(['message' => 'Prestao/la si pratiti ovog korisnika.']);
    }
}
