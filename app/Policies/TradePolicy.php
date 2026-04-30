<?php

namespace App\Policies;

use App\Models\Trade;
use App\Models\User;

class TradePolicy
{
    public function view(User $user, Trade $trade): bool
    {
        return $user->id === $trade->buyer_id || $user->id === $trade->seller_id;
    }

    public function respond(User $user, Trade $trade): bool
    {
        return $user->id === $trade->seller_id && $trade->isActive();
    }
}
