<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderStatusService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function transition(Order $order, OrderStatus $status, ?User $actor = null, ?string $note = null): Order
    {
        if (! $order->canTransitionTo($status)) {
            throw ValidationException::withMessages([
                'status' => "Order {$order->order_number} cannot move from {$order->status->label()} to {$status->label()}.",
            ]);
        }

        if (in_array($status, [OrderStatus::Cancelled, OrderStatus::Rejected], true) && $order->isPaid()) {
            throw ValidationException::withMessages([
                'status' => 'Paid orders cannot be cancelled from the dashboard. A refund must be completed first.',
            ]);
        }

        if ($status === OrderStatus::ReadyForPickup && $order->fulfillment_type->value !== 'pickup') {
            throw ValidationException::withMessages([
                'status' => 'This order is for delivery, not pickup.',
            ]);
        }

        if ($status === OrderStatus::OutForDelivery && $order->fulfillment_type->value !== 'delivery') {
            throw ValidationException::withMessages([
                'status' => 'This order is for pickup, not delivery.',
            ]);
        }

        DB::transaction(function () use ($order, $status, $note) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $from = $order->status;
            $order->status = $status;

            if ($status === OrderStatus::Confirmed) {
                $order->confirmed_at = now();
            }
            if ($status === OrderStatus::Completed) {
                $order->completed_at = now();
            }
            if (in_array($status, [OrderStatus::Cancelled, OrderStatus::Rejected], true)) {
                $order->cancelled_at = now();
            }

            $order->save();

            if (in_array($status, [OrderStatus::Cancelled, OrderStatus::Rejected], true)) {
                $this->inventoryService->release($order, $note ?? 'Order '.$status->value);
            }

            if ($order->customer_id) {
                $order->customer->appNotifications()->create([
                    'shop_id' => $order->shop_id,
                    'type' => 'order_status',
                    'title' => 'Order '.$order->order_number.' updated',
                    'body' => $status->customerLabel(),
                    'data' => ['order_id' => $order->id, 'status' => $status->value],
                ]);
            }

            $this->activityLogger->log(
                'order.status_changed',
                "Order {$order->order_number} moved to {$status->label()}",
                $order,
                $order->shop,
                ['from' => $from->value, 'to' => $status->value, 'note' => $note]
            );
        });

        return $order->fresh(['items', 'payment', 'shop']);
    }
}
