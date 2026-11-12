<?php

namespace App\Enums;

enum FulfillmentType: string
{
    case Pickup = 'pickup';
    case Delivery = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'Pickup',
            self::Delivery => 'Delivery',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Pickup => 'Collect your cake from the bakery.',
            self::Delivery => 'Have your cake delivered to your door.',
        };
    }
}
