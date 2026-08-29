<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return null;
    }

    public function update(User $user, Product $product): bool
    {
        return $user->id === $product->seller_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->id === $product->seller_id;
    }

    public function publish(User $user, Product $product): bool
    {
        return $user->id === $product->seller_id;
    }

    /**
     * Ownership only. Whether the listing is *currently* refreshable (status,
     * age, cooldown) is App\Services\ListingRefreshService's business — admins
     * bypass this policy via before(), but not those rules.
     */
    public function refresh(User $user, Product $product): bool
    {
        return $user->id === $product->seller_id;
    }
}
