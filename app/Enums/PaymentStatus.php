<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
            self::Expired => 'Expired',
            self::Refunded => 'Refunded',
            self::PartiallyRefunded => 'Partially refunded',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Unpaid => 'bg-stone-100 text-stone-600 ring-stone-200',
            self::Pending => 'bg-amber-50 text-amber-800 ring-amber-200',
            self::Paid => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            self::Failed, self::Expired => 'bg-rose-50 text-rose-800 ring-rose-200',
            self::Refunded, self::PartiallyRefunded => 'bg-violet-50 text-violet-800 ring-violet-200',
        };
    }

    public function isSuccessful(): bool
    {
        return in_array($this, [self::Paid, self::PartiallyRefunded], true);
    }
}
