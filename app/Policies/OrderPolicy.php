<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isShopOwner() || $user->isCustomer() || $user->isSuperAdmin();
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isShopOwner()) {
            return $user->shop?->id === $order->shop_id;
        }

        return (int) $order->customer_id === $user->id
            || strcasecmp($order->customer_email, $user->email) === 0;
    }

    public function update(User $user, Order $order): bool
    {
        return $user->shop?->id === $order->shop_id;
    }

    public function cancel(User $user, Order $order): bool
    {
        if ($user->isShopOwner()) {
            return $user->shop?->id === $order->shop_id;
        }

        return ((int) $order->customer_id === $user->id
            || strcasecmp($order->customer_email, $user->email) === 0)
            && $order->isCancellableByCustomer();
    }
}
