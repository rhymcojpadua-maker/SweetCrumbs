<?php

namespace App\Enums;

enum ShopStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending review',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Rejected => 'Rejected',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::Active => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::Suspended => 'bg-rose-50 text-rose-700 ring-rose-200',
            self::Rejected => 'bg-stone-100 text-stone-600 ring-stone-200',
        };
    }
}
