<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case ShopOwner = 'shop_owner';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super administrator',
            self::ShopOwner => 'Shop owner',
            self::Customer => 'Customer',
        };
    }

    public function dashboardPath(): ?string
    {
        return match ($this) {
            self::SuperAdmin => '/admin',
            self::ShopOwner => '/dashboard',
            self::Customer => null,
        };
    }
}
