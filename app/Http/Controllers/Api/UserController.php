<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserDeletionService;
use App\Services\ViewCountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        $users = User::query()
            ->where('email', 'not like', '%@deleted.tavan')
            ->where('role', '!=', 'super_admin')
            ->whereNull('deletion_requested_at')
            ->where(fn ($q) => $q->whereNull('banned_until')->orWhere('banned_until', '<=', now()))
            ->withCount(['products as item_count' => fn ($q) => $q->where('status', 'active')])
            ->when($query, function ($q) use ($query) {
                $q->where(function ($inner) use ($query) {
                    $inner->where('username', 'like', "%{$query}%")
                          ->orWhere('name', 'like', "%{$query}%");
                });
            })
            ->orderBy('rating', 'desc')
            ->limit(30)
            ->get();

        return response()->json(['data' => UserResource::collection($users)]);
    }

    /**
     * Find a user by username or ULID (for both public profile and self-lookup).
     */
    private function findByUsernameOrId(string $usernameOrId): User
    {
        // ULIDs are 26 chars of [0-9A-Z]
        if (ctype_alnum($usernameOrId) && strlen($usernameOrId) === 26) {
            return User::where('id', $usernameOrId)
                ->withCount(['products as item_count' => fn ($q) => $q->where('status', 'active')])
                ->firstOrFail();
        }

        return User::where('username', $usernameOrId)
            ->withCount(['products as item_count' => fn ($q) => $q->where('status', 'active')])
            ->firstOrFail();
    }

    public function show(Request $request, string $username, ViewCountService $viewCount): JsonResponse
    {
        $user = $this->findByUsernameOrId($username);

        $authUser = $request->user() ?? \Illuminate\Support\Facades\Auth::guard('sanctum')->user();

        if ($authUser && $authUser->id !== $user->id) {
            $blocked = \App\Models\UserBlock::where(function ($q) use ($authUser, $user) {
                $q->where('blocker_id', $authUser->id)->where('blocked_id', $user->id);
            })->orWhere(function ($q) use ($authUser, $user) {
                $q->where('blocker_id', $user->id)->where('blocked_id', $authUser->id);
            })->exists();

            abort_if($blocked, 404);
        }

        if ($user->deletion_requested_at || $user->isBanned() || $user->role === 'super_admin') {
            abort(404);
        }

        $viewCount->incrementProfileView($request, $user);

        return response()->json(['data' => new UserResource($user)]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $request->user()->update($request->validated());

        return response()->json(['data' => new UserResource($request->user()->fresh())]);
    }

    public function getNotificationPref(Request $request): JsonResponse
    {
        return response()->json([
            'data' => ['notifications_enabled' => (bool) $request->user()->notifications_enabled],
        ]);
    }

    public function setNotificationPref(Request $request): JsonResponse
    {
        $data = $request->validate(['notifications_enabled' => ['required', 'boolean']]);

        $request->user()->update(['notifications_enabled' => $data['notifications_enabled']]);

        return response()->json([
            'data' => ['notifications_enabled' => (bool) $data['notifications_enabled']],
        ]);
    }

    public function destroy(Request $request, UserDeletionService $deletion): JsonResponse
    {
        $user = $request->user();

        $deletion->cancelActiveOrders($user);

        $user->update(['deletion_requested_at' => now()]);
        $user->tokens()->delete();

        return response()->json(null, 204);
    }

    public function cancelDeletion(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->update(['deletion_requested_at' => null]);

        $user->tokens()->delete();
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'data' => [
                'user'  => new UserResource($user->fresh()),
                'token' => $token,
            ],
        ]);
    }

    public function products(Request $request, string $username): JsonResponse
    {
        $user = User::where(function ($q) use ($username) {
            if (ctype_alnum($username) && strlen($username) === 26) {
                $q->where('id', $username);
            } else {
                $q->where('username', $username);
            }
        })->where('role', '!=', 'super_admin')->firstOrFail();

        // This is a public route, so $request->user() is null even when a Bearer token
        // is present. Resolve the Sanctum user manually (same pattern as ProductController).
        $authUser = $request->user() ?? \Illuminate\Support\Facades\Auth::guard('sanctum')->user();

        $requestedStatus     = $request->get('status', 'active');
        $authenticatedUserId = $authUser?->id;
        $isOwner             = $authenticatedUserId === $user->id;

        // active + reserved + sold are public.
        // draft + pending_review are owner-only — never exposed to other users.
        $ownerOnlyStatuses  = ['draft', 'pending_review'];
        $publicStatuses     = ['active', 'reserved', 'sold'];

        $allowedStatus = match (true) {
            in_array($requestedStatus, $publicStatuses)                          => $requestedStatus,
            in_array($requestedStatus, $ownerOnlyStatuses) && $isOwner           => $requestedStatus,
            default                                                               => 'active',
        };

        $perPage = min(max((int) $request->get('per_page', 20), 1), 100);

        $products = $user->products()
            ->where('status', $allowedStatus)
            ->with(['images', 'brand'])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => ProductResource::collection($products->items()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
            ],
        ]);
    }
}
