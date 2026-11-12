<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case Reservation = 'reservation';
    case Release = 'release';
    case Adjustment = 'adjustment';
    case Restock = 'restock';

    public function label(): string
    {
        return match ($this) {
            self::Reservation => 'Order reservation',
            self::Release => 'Reservation released',
            self::Adjustment => 'Manual adjustment',
            self::Restock => 'Stock added',
        };
    }
}
