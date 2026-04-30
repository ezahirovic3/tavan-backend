<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->buyer_id || $user->id === $order->seller_id;
    }

    public function sellerAction(User $user, Order $order): bool
    {
        return $user->id === $order->seller_id;
    }

    public function buyerAction(User $user, Order $order): bool
    {
        return $user->id === $order->buyer_id;
    }
}
