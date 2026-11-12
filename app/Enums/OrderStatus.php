<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case PendingConfirmation = 'pending_confirmation';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case ReadyForPickup = 'ready_for_pickup';
    case OutForDelivery = 'out_for_delivery';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Pending payment',
            self::PendingConfirmation => 'Pending confirmation',
            self::Confirmed => 'Confirmed',
            self::Preparing => 'Preparing',
            self::ReadyForPickup => 'Ready for pickup',
            self::OutForDelivery => 'Out for delivery',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Rejected => 'Rejected',
        };
    }

    public function customerLabel(): string
    {
        return match ($this) {
            self::PendingPayment => 'Awaiting payment',
            self::PendingConfirmation => 'Order received',
            self::Confirmed => 'Confirmed by the bakery',
            self::Preparing => 'Your cake is being prepared',
            self::ReadyForPickup => 'Ready for pickup',
            self::OutForDelivery => 'On the way',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Rejected => 'Not accepted',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::PendingPayment => 'bg-amber-50 text-amber-800 ring-amber-200',
            self::PendingConfirmation => 'bg-sky-50 text-sky-800 ring-sky-200',
            self::Confirmed => 'bg-blue-50 text-blue-800 ring-blue-200',
            self::Preparing => 'bg-violet-50 text-violet-800 ring-violet-200',
            self::ReadyForPickup, self::OutForDelivery => 'bg-indigo-50 text-indigo-800 ring-indigo-200',
            self::Completed => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            self::Cancelled, self::Rejected => 'bg-stone-100 text-stone-600 ring-stone-200',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::Rejected], true);
    }

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingPayment => [self::PendingConfirmation, self::Cancelled],
            self::PendingConfirmation => [self::Confirmed, self::Rejected, self::Cancelled],
            self::Confirmed => [self::Preparing, self::Cancelled],
            self::Preparing => [self::ReadyForPickup, self::OutForDelivery, self::Cancelled],
            self::ReadyForPickup => [self::Completed, self::Cancelled],
            self::OutForDelivery => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled, self::Rejected => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function isActive(): bool
    {
        return ! $this->isTerminal() && $this !== self::PendingPayment;
    }
}
