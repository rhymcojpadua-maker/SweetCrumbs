<?php

namespace App\Policies;

use App\Models\Shop;
use App\Models\User;

class ShopPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isShopOwner();
    }

    public function view(User $user, Shop $shop): bool
    {
        return $user->isSuperAdmin() || $user->shop?->id === $shop->id;
    }

    public function create(User $user): bool
    {
        return $user->isShopOwner() && ! $user->shop;
    }

    public function update(User $user, Shop $shop): bool
    {
        return $user->shop?->id === $shop->id;
    }

    public function approve(User $user, Shop $shop): bool
    {
        return $user->isSuperAdmin();
    }

    public function suspend(User $user, Shop $shop): bool
    {
        return $user->isSuperAdmin() && $shop->id !== null;
    }

    public function delete(User $user, Shop $shop): bool
    {
        return $user->isSuperAdmin();
    }
}
