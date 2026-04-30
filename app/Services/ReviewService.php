<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function create(Order $order, User $reviewer, array $data): Review
    {
        return DB::transaction(function () use ($order, $reviewer, $data) {
            $isBuyer = $reviewer->id === $order->buyer_id;
            $role    = $isBuyer ? 'seller' : 'buyer'; // reviewing the other party

            $review = Review::create([
                'order_id'    => $order->id,
                'reviewer_id' => $reviewer->id,
                'reviewed_id' => $isBuyer ? $order->seller_id : $order->buyer_id,
                'rating'      => $data['rating'],
                'comment'     => $data['comment'] ?? null,
                'role'        => $role,
            ]);

            $this->recalculateRating($review->reviewed_id);

            return $review;
        });
    }

    private function recalculateRating(string $userId): void
    {
        $stats = Review::where('reviewed_id', $userId)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')
            ->first();

        User::where('id', $userId)->update([
            'rating'        => round($stats->avg_rating, 2),
            'total_reviews' => $stats->total,
        ]);
    }
}
