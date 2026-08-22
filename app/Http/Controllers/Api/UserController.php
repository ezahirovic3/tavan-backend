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

        $user->loadCount(['followers', 'following']);

        if ($authUser) {
            $user->is_following = \App\Models\Follow::where('follower_id', $authUser->id)
                ->where('followed_id', $user->id)
                ->exists();
        }

        return response()->json(['data' => new UserResource($user)]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $request->user()->update($request->validated());

        return response()->json(['data' => new UserResource($request->user()->fresh())]);
    }

    /**
     * The 5 opt-out categories, keyed by their API field name → users column.
     * Same set as App\Support\NotificationCategory, just phrased as the
     * user-facing preference fields rather than push-type buckets.
     */
    private const NOTIFICATION_CATEGORY_FIELDS = [
        'notify_messages'      => 'notify_messages',
        'notify_orders'        => 'notify_orders',
        'notify_activity'      => 'notify_activity',
        'notify_price_drops'   => 'notify_price_drops',
        'notify_announcements' => 'notify_announcements',
    ];

    public function getNotificationPref(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->notificationPrefResponse($request->user()),
        ]);
    }

    public function setNotificationPref(Request $request): JsonResponse
    {
        $data = $request->validate([
            'notifications_enabled' => ['sometimes', 'boolean'],
            'notify_messages'       => ['sometimes', 'boolean'],
            'notify_orders'         => ['sometimes', 'boolean'],
            'notify_activity'       => ['sometimes', 'boolean'],
            'notify_price_drops'    => ['sometimes', 'boolean'],
            'notify_announcements'  => ['sometimes', 'boolean'],
        ]);

        if (empty($data)) {
            return response()->json(['message' => 'Nema promjena.'], 422);
        }

        $request->user()->update($data);

        return response()->json([
            'data' => $this->notificationPrefResponse($request->user()->fresh()),
        ]);
    }

    private function notificationPrefResponse(User $user): array
    {
        return [
            'notifications_enabled' => (bool) $user->notifications_enabled,
            ...array_map(
                fn (string $column) => (bool) $user->{$column},
                self::NOTIFICATION_CATEGORY_FIELDS,
            ),
        ];
    }

    public function destroy(Request $request, UserDeletionService $deletion): JsonResponse
    {
        $user = $request->user();

        $deletion->cancelActiveOrders($user);

        $user->update(['deletion_requested_at' => now()]);
        $user->revokeTokens();

        return response()->json(null, 204);
    }

    public function cancelDeletion(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->update(['deletion_requested_at' => null]);

        $user->revokeTokens();
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

        // Pin it onto the request so ProductResource::collection() below doesn't
        // re-resolve the sanctum token (cache + DB) once per product returned.
        $request->setUserResolver(fn () => $authUser);

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

        // Category/attribute/brand/price/sort filters — same params and query
        // logic as the global feed, see Product::scopeApplyFilters().
        $products = $user->products()
            ->where('status', $allowedStatus)
            ->with(['images', 'brand'])
            ->applyFilters($request)
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

    /** GET /users/{username}/followers — public, read-only, same shape as products() above. */
    public function followers(Request $request, string $username): JsonResponse
    {
        return $this->paginatedFollowRelation($request, $username, 'followers');
    }

    /** GET /users/{username}/following */
    public function following(Request $request, string $username): JsonResponse
    {
        return $this->paginatedFollowRelation($request, $username, 'following');
    }

    private function paginatedFollowRelation(Request $request, string $username, string $relation): JsonResponse
    {
        $user = User::where(function ($q) use ($username) {
            if (ctype_alnum($username) && strlen($username) === 26) {
                $q->where('id', $username);
            } else {
                $q->where('username', $username);
            }
        })->where('role', '!=', 'super_admin')->firstOrFail();

        $perPage = min(max((int) $request->get('per_page', 20), 1), 100);

        $related = $user->{$relation}()->orderByDesc('follows.created_at')->paginate($perPage);

        // Per-row follow state, for the "Prati"/"Pratim" button on each list
        // row — batched into one query rather than N+1 per item. Guests
        // (no auth) just get every row as "Prati"; tapping it routes them to
        // sign-in, same as the follow button everywhere else.
        $authUser = $request->user() ?? \Illuminate\Support\Facades\Auth::guard('sanctum')->user();

        if ($authUser) {
            $itemIds = collect($related->items())->pluck('id');
            $viewerFollowsIds = \App\Models\Follow::where('follower_id', $authUser->id)
                ->whereIn('followed_id', $itemIds)
                ->pluck('followed_id');

            foreach ($related->items() as $item) {
                $item->is_following = $viewerFollowsIds->contains($item->id);
            }
        }

        return response()->json([
            'data' => UserResource::collection($related->items()),
            'meta' => [
                'current_page' => $related->currentPage(),
                'last_page'    => $related->lastPage(),
                'per_page'     => $related->perPage(),
                'total'        => $related->total(),
            ],
        ]);
    }
}
