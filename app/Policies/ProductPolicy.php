<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isShopOwner();
    }

    public function view(User $user, Product $product): bool
    {
        return $user->shop?->id === $product->shop_id;
    }

    public function create(User $user): bool
    {
        return $user->isShopOwner() && $user->shop?->isActive();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->shop?->id === $product->shop_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->shop?->id === $product->shop_id;
    }
}
