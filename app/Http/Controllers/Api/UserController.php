<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        $users = User::query()
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

    public function show(string $username): JsonResponse
    {
        $user = $this->findByUsernameOrId($username);

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

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke all tokens
        $user->tokens()->delete();

        // Anonymize instead of hard-delete to preserve order/review history
        $user->update([
            'name'     => 'Obrisani korisnik',
            'username' => 'deleted_' . $user->id,
            'email'    => 'deleted_' . $user->id . '@deleted.tavan',
            'avatar'   => null,
            'bio'      => null,
            'phone'    => null,
        ]);

        return response()->json(null, 204);
    }

    public function products(Request $request, string $username): JsonResponse
    {
        $user = User::where(function ($q) use ($username) {
            if (ctype_alnum($username) && strlen($username) === 26) {
                $q->where('id', $username);
            } else {
                $q->where('username', $username);
            }
        })->firstOrFail();

        $requestedStatus     = $request->get('status', 'active');
        $authenticatedUserId = $request->user()?->id;
        $isOwner             = $authenticatedUserId === $user->id;

        // active + reserved + sold are public; draft is owner-only
        $allowedStatus = match (true) {
            in_array($requestedStatus, ['active', 'reserved', 'sold']) => $requestedStatus,
            $requestedStatus === 'draft' && $isOwner                   => 'draft',
            default                                                    => 'active',
        };

        $products = $user->products()
            ->where('status', $allowedStatus)
            ->with(['images', 'brand'])
            ->latest()
            ->paginate(20);

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
