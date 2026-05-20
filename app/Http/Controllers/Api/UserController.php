<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\UserResource;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\User;
use App\Services\ConversationService;
use App\Services\ImageService;
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

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $conversations = app(ConversationService::class);

        // 1. Cancel all active orders and notify the other party via system message
        $activeOrders = Order::where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id);
            })
            ->whereIn('status', ['pending', 'accepted', 'shipped'])
            ->with('product')
            ->get();

        foreach ($activeOrders as $order) {
            $order->update(['status' => 'cancelled']);

            // Restore the product to active if the deleted user was the buyer
            // (seller's own products will be deleted below anyway)
            if ($order->buyer_id === $user->id && $order->product?->status === 'reserved') {
                $order->product->update(['status' => 'active']);
            }

            $conversation = $conversations->findOrCreate($order->buyer_id, $order->seller_id);
            $conversations->sendSystemMessage(
                $conversation,
                $user->id,
                'system_status',
                ['orderId' => $order->id, 'status' => 'cancelled'],
                'Narudžba je otkazana jer je korisnik obrisao račun.',
            );
        }

        // 2. Lock all conversations this user is part of — preserve history, block new messages
        Conversation::where('participant_one_id', $user->id)
            ->orWhere('participant_two_id', $user->id)
            ->update(['allow_replies' => false]);

        // 3. Revoke all tokens
        $user->tokens()->delete();

        // 4. Delete avatar from R2
        if ($user->avatar) {
            app(ImageService::class)->deleteByUrl($user->avatar);
        }

        // 5. Delete non-sold products + their R2 images and wishlisted entries
        //    (the Product deleting hook handles R2 and wishlist cleanup)
        $user->products()->whereNotIn('status', ['sold'])->get()
            ->each(fn ($product) => $product->delete());

        // 6. Anonymize instead of hard-delete to preserve order/review history
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
