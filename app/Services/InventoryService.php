<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    public function reserve(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->inventory_reserved) {
                return;
            }

            $items = $order->items()->with('product')->get();
            $products = [];

            foreach ($items as $item) {
                if (! $item->product || (int) $item->product->shop_id !== (int) $order->shop_id) {
                    throw new RuntimeException('This order contains a product that is no longer available.');
                }

                $product = Product::query()
                    ->where('shop_id', $order->shop_id)
                    ->lockForUpdate()
                    ->findOrFail($item->product_id);

                $requested = (int) $item->quantity;
                $before = (int) $product->stock_quantity;

                if ($before < $requested) {
                    throw new RuntimeException("Not enough stock for {$product->name}. Only {$before} left.");
                }

                $product->stock_quantity = $before - $requested;
                $product->save();

                InventoryMovement::create([
                    'shop_id' => $order->shop_id,
                    'product_id' => $product->id,
                    'order_id' => $order->id,
                    'type' => InventoryMovementType::Reservation,
                    'quantity' => -$requested,
                    'stock_before' => $before,
                    'stock_after' => $product->stock_quantity,
                    'reason' => "Reserved for order {$order->order_number}",
                ]);

                $products[] = $product;
            }

            $order->inventory_reserved = true;
            $order->save();
        });
    }

    public function release(Order $order, ?string $reason = null): void
    {
        DB::transaction(function () use ($order, $reason): void {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $order->inventory_reserved) {
                return;
            }

            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $product = Product::query()->lockForUpdate()->find($item->product_id);
                if (! $product) {
                    continue;
                }

                $before = (int) $product->stock_quantity;
                $product->stock_quantity = $before + (int) $item->quantity;
                $product->save();

                InventoryMovement::create([
                    'shop_id' => $order->shop_id,
                    'product_id' => $product->id,
                    'order_id' => $order->id,
                    'type' => InventoryMovementType::Release,
                    'quantity' => (int) $item->quantity,
                    'stock_before' => $before,
                    'stock_after' => $product->stock_quantity,
                    'reason' => $reason ?? "Released from order {$order->order_number}",
                ]);
            }

            $order->inventory_reserved = false;
            $order->save();
        });
    }

    public function adjust(Product $product, int $newQuantity, string $reason, ?User $user = null): InventoryMovement
    {
        return DB::transaction(function () use ($product, $newQuantity, $reason, $user): InventoryMovement {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            $before = (int) $product->stock_quantity;
            $after = max(0, $newQuantity);
            $product->stock_quantity = $after;
            $product->save();

            return InventoryMovement::create([
                'shop_id' => $product->shop_id,
                'product_id' => $product->id,
                'created_by' => $user?->id,
                'type' => $after >= $before ? InventoryMovementType::Restock : InventoryMovementType::Adjustment,
                'quantity' => $after - $before,
                'stock_before' => $before,
                'stock_after' => $after,
                'reason' => $reason,
            ]);
        });
    }

    public function lowStockCount(int $shopId): int
    {
        return Product::query()
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->count();
    }
}
