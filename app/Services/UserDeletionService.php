<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Order;
use App\Models\User;

class UserDeletionService
{
    public function __construct(
        private readonly ConversationService $conversations,
        private readonly ImageService $images,
    ) {}

    public function cancelActiveOrders(User $user): void
    {
        $activeOrders = Order::where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id);
            })
            ->whereIn('status', ['pending', 'accepted', 'shipped'])
            ->with('product')
            ->get();

        foreach ($activeOrders as $order) {
            $order->update(['status' => 'cancelled']);

            if ($order->buyer_id === $user->id && $order->product?->status === 'reserved') {
                $order->product->update(['status' => 'active']);
            }

            $conversation = $this->conversations->findOrCreate($order->buyer_id, $order->seller_id);
            $this->conversations->sendSystemMessage(
                $conversation,
                $user->id,
                'system_status',
                ['orderId' => $order->id, 'status' => 'cancelled'],
                'Narudžba je otkazana jer je korisnik obrisao račun.',
            );
        }
    }

    public function anonymize(User $user): void
    {
        // 1. Cancel any remaining active orders (safety net — normally done on deletion request)
        $this->cancelActiveOrders($user);

        // 2. Lock conversations — preserve history, block new messages
        Conversation::where('participant_one_id', $user->id)
            ->orWhere('participant_two_id', $user->id)
            ->update(['allow_replies' => false]);

        // 3. Revoke all tokens
        $user->tokens()->delete();

        // 4. Delete avatar from R2
        if ($user->avatar) {
            $this->images->deleteByUrl($user->avatar);
        }

        // 5. Delete non-sold products + their R2 images
        $user->products()->whereNotIn('status', ['sold'])->get()
            ->each(fn ($product) => $product->delete());

        // 6. Anonymize — preserve row for order/review history
        $user->update([
            'name'                   => 'Obrisani korisnik',
            'username'               => 'deleted_' . $user->id,
            'email'                  => 'deleted_' . $user->id . '@deleted.tavan',
            'avatar'                 => null,
            'bio'                    => null,
            'phone'                  => null,
            'deletion_requested_at'  => null,
        ]);
    }
}
