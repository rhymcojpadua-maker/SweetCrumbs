<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isShopOwner();
    }

    public function view(User $user, Category $category): bool
    {
        return $user->shop?->id === $category->shop_id;
    }

    public function create(User $user): bool
    {
        return $user->isShopOwner() && $user->shop?->isActive();
    }

    public function update(User $user, Category $category): bool
    {
        return $user->shop?->id === $category->shop_id;
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->shop?->id === $category->shop_id;
    }
}
