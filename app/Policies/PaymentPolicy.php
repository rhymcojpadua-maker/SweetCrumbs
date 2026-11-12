<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isShopOwner() || $user->isSuperAdmin();
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->isSuperAdmin() || $user->shop?->id === $payment->shop_id;
    }
}
