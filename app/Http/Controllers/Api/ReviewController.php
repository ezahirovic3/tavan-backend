<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Services\PushNotificationService;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService,
        private readonly PushNotificationService $push,
    ) {}

    public function show(Request $request, Review $review): JsonResponse
    {
        $user = $request->user();

        abort_if(
            $user->id !== $review->reviewer_id && $user->id !== $review->reviewed_id,
            403,
            'Nemate pristup ovoj recenziji.'
        );

        return response()->json(['data' => new ReviewResource($review->load('reviewer', 'reviewed'))]);
    }

    public function store(StoreReviewRequest $request, Order $order): JsonResponse
    {
        $user = $request->user();

        abort_if(
            $user->id !== $order->buyer_id && $user->id !== $order->seller_id,
            403,
            'Nemate pristup ovoj narudžbi.'
        );
        abort_if($order->status !== 'completed', 422, 'Recenzija je moguća samo za završene narudžbe.');
        abort_if(
            Review::where('order_id', $order->id)->where('reviewer_id', $user->id)->exists(),
            422,
            'Već ste ostavili recenziju za ovu narudžbu.'
        );

        $review = $this->reviewService->create($order, $user, $request->validated());

        $stars = str_repeat('⭐', (int) $review->rating);
        $this->push->sendToUser(
            $review->reviewed_id,
            'Nova recenzija ' . $stars,
            $user->name . ' je ostavio/la recenziju za vas.',
            ['type' => 'review', 'reviewId' => $review->id],
        );

        return response()->json(['data' => new ReviewResource($review->load('reviewer', 'reviewed'))], 201);
    }

    public function userReviews(Request $request, string $username): JsonResponse
    {
        // Accept both username and ULID
        $user = User::where(function ($q) use ($username) {
            if (ctype_alnum($username) && strlen($username) === 26) {
                $q->where('id', $username);
            } else {
                $q->where('username', $username);
            }
        })->firstOrFail();

        $reviews = Review::where('reviewed_id', $user->id)
            ->with('reviewer')
            ->latest('created_at')
            ->paginate(20);

        return response()->json([
            'data' => ReviewResource::collection($reviews->items()),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page'    => $reviews->lastPage(),
                'per_page'     => $reviews->perPage(),
                'total'        => $reviews->total(),
            ],
        ]);
    }
}
