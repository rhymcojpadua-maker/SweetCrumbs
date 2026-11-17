<?php

namespace App\Services;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShopCustomer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly ScheduleService $scheduleService,
        private readonly InventoryService $inventoryService,
    ) {}

    public function placeOrder(Request $request, array $data): Order
    {
        $cart = $this->cartService->currentCart($request, false);

        if (! $cart || $cart->items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Your basket is empty.']);
        }

        $cart->loadMissing(['items.product.shop', 'items.variant']);
        $shop = $cart->shop;

        if (! $shop || ! $shop->isActive() || ! $cart->items->every(fn ($item) => $item->product?->shop_id === $shop->id && $item->product?->is_active)) {
            throw ValidationException::withMessages(['cart' => 'One or more items are no longer available. Please review your basket.']);
        }

        $type = FulfillmentType::from($data['fulfillment_type']);
        $cashOnDelivery = $data['payment_method'] === 'cash_on_delivery';
        if ($cashOnDelivery && $type !== FulfillmentType::Delivery) {
            throw ValidationException::withMessages([
                'payment_method' => 'Cash on delivery is only available for delivery orders.',
            ]);
        }

        $settings = $shop->settings;
        if ($type === FulfillmentType::Pickup && ! $settings?->pickup_enabled) {
            throw ValidationException::withMessages(['fulfillment_type' => 'This bakery does not offer pickup.']);
        }
        if ($type === FulfillmentType::Delivery && ! $settings?->delivery_enabled) {
            throw ValidationException::withMessages(['fulfillment_type' => 'This bakery does not offer delivery.']);
        }

        if ($type === FulfillmentType::Delivery && blank($data['delivery_address'] ?? null)) {
            throw ValidationException::withMessages(['delivery_address' => 'Please provide a delivery address.']);
        }

        $leadHours = (int) $cart->items->max(fn ($item) => (int) $item->product->preparation_hours);
        $this->scheduleService->validateSelection(
            $shop,
            $type,
            $data['scheduled_date'],
            $data['scheduled_time'],
            $leadHours
        );

        $pricedItems = $cart->items->map(function ($item) use ($shop) {
            $product = $item->product;
            $variant = $item->variant;

            if ((int) $product->shop_id !== (int) $shop->id || ! $product->is_active) {
                throw ValidationException::withMessages(['cart' => "{$product->name} is no longer available."]);
            }

            if ($variant && (int) $variant->product_id !== (int) $product->id) {
                throw ValidationException::withMessages(['cart' => "The selected size for {$product->name} is invalid."]);
            }

            if ($item->quantity > $product->stock_quantity) {
                throw ValidationException::withMessages([
                    'cart' => "Only {$product->stock_quantity} of {$product->name} are available.",
                ]);
            }

            $selected = collect($item->customizations ?? [])->mapWithKeys(fn ($option, $key) => [$key => $option['value'] ?? null])->all();
            $unitPrice = $product->priceFor($variant) + $product->customizationPrice($selected);

            return [
                'item' => $item,
                'product' => $product,
                'variant' => $variant,
                'unit_price' => $unitPrice,
                'line_total' => round($unitPrice * $item->quantity, 2),
                'customizations' => $item->customizations,
            ];
        });

        $subtotal = round($pricedItems->sum('line_total'), 2);
        $deliveryFee = 0.0;
        if ($type === FulfillmentType::Delivery) {
            $deliveryFee = (float) $settings->delivery_fee;
            if ($settings->free_delivery_threshold !== null && $subtotal >= (float) $settings->free_delivery_threshold) {
                $deliveryFee = 0.0;
            }
        }

        $total = round($subtotal + $deliveryFee, 2);

        $order = DB::transaction(function () use ($request, $data, $cart, $shop, $type, $pricedItems, $subtotal, $deliveryFee, $total, $cashOnDelivery) {
            $order = Order::create([
                'shop_id' => $shop->id,
                'customer_id' => $request->user()?->id,
                'customer_name' => $data['customer_name'],
                'customer_email' => strtolower($data['customer_email']),
                'customer_phone' => $data['customer_phone'],
                'fulfillment_type' => $type,
                'delivery_address' => $type === FulfillmentType::Delivery ? $data['delivery_address'] : null,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'status' => $cashOnDelivery ? OrderStatus::PendingConfirmation : OrderStatus::PendingPayment,
                'payment_status' => PaymentStatus::Unpaid,
                'scheduled_date' => $data['scheduled_date'],
                'scheduled_time' => $data['scheduled_time'],
                'notes' => $data['notes'] ?? null,
                'payment_method' => $data['payment_method'],
                'placed_at' => now(),
            ]);

            foreach ($pricedItems as $priced) {
                $order->items()->create([
                    'product_id' => $priced['product']->id,
                    'product_variant_id' => $priced['variant']?->id,
                    'product_name' => $priced['product']->name,
                    'variant_name' => $priced['variant']?->name,
                    'quantity' => $priced['item']->quantity,
                    'unit_price' => $priced['unit_price'],
                    'line_total' => $priced['line_total'],
                    'customizations' => $priced['customizations'],
                    'special_instructions' => $priced['item']->special_instructions,
                ]);
            }

            $this->inventoryService->reserve($order);

            ShopCustomer::updateOrCreate(
                ['shop_id' => $shop->id, 'email' => strtolower($data['customer_email'])],
                [
                    'user_id' => $request->user()?->id,
                    'name' => $data['customer_name'],
                    'phone' => $data['customer_phone'],
                    'last_order_at' => now(),
                ]
            );

            $cart->items()->delete();
            $cart->delete();

            Payment::create([
                'order_id' => $order->id,
                'shop_id' => $shop->id,
                'provider' => $cashOnDelivery ? 'manual' : 'xendit',
                'reference_number' => $order->order_number,
                'amount' => $total,
                'currency' => config('services.xendit.currency', 'PHP'),
                'status' => PaymentStatus::Unpaid,
                'payment_method' => $cashOnDelivery ? 'cash_on_delivery' : null,
            ]);

            return $order;
        });

        ShopCustomer::query()
            ->where('shop_id', $shop->id)
            ->where('email', strtolower($data['customer_email']))
            ->update([
                'order_count' => DB::raw('order_count + 1'),
                'total_spent' => DB::raw('total_spent + '.$total),
            ]);

        $this->notifyNewOrder($order);

        return $order->load(['items', 'payment']);
    }

    private function notifyNewOrder(Order $order): void
    {
        $order->shop->owner->appNotifications()->create([
            'shop_id' => $order->shop_id,
            'type' => 'new_order',
            'title' => 'New order '.$order->order_number,
            'body' => $order->customer_name.' placed an order for '.$order->itemSummary().'.',
            'data' => ['order_id' => $order->id],
        ]);

        User::query()->where('role', 'super_admin')->get()->each(function ($admin) use ($order) {
            $admin->appNotifications()->create([
                'shop_id' => $order->shop_id,
                'type' => 'platform_order',
                'title' => 'New marketplace order',
                'body' => $order->shop->name.' received order '.$order->order_number.'.',
                'data' => ['order_id' => $order->id],
            ]);
        });
    }
}
