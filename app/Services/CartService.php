<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function currentCart(Request $request, bool $create = true): ?Cart
    {
        $user = $request->user();
        $cart = $user
            ? Cart::query()->where('user_id', $user->id)->first()
            : Cart::query()->where('session_id', $request->session()->getId())->first();

        if (! $cart && $user) {
            $guestCart = Cart::query()->where('session_id', $request->session()->getId())->first();
            if ($guestCart) {
                $guestCart->user_id = $user->id;
                $guestCart->save();
                $cart = $guestCart;
            }
        }

        if (! $cart && $create) {
            $cart = Cart::create([
                'user_id' => $user?->id,
                'session_id' => $user ? null : $request->session()->getId(),
            ]);
        }

        return $cart?->load(['items.product.shop', 'items.variant', 'shop']);
    }

    public function add(Request $request, Product $product, ?ProductVariant $variant, int $quantity, array $customizations, ?string $instructions): CartItem
    {
        if (! $product->is_active || ! $product->shop?->isActive()) {
            throw ValidationException::withMessages(['product' => 'This cake is not currently available.']);
        }

        if ($variant && (int) $variant->product_id !== (int) $product->id) {
            throw ValidationException::withMessages(['variant' => 'Invalid cake size selected.']);
        }

        $cart = $this->currentCart($request, true);

        if ($cart->shop_id && (int) $cart->shop_id !== (int) $product->shop_id && $cart->items()->exists()) {
            throw ValidationException::withMessages([
                'cart' => 'Your basket contains cakes from another bakery. SweetCrumbs completes one bakery per order.',
            ]);
        }

        $optionsHash = $this->optionsHash($variant, $customizations);
        $existing = $cart->items()
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variant?->id)
            ->where('options_hash', $optionsHash)
            ->first();

        $newQuantity = ($existing?->quantity ?? 0) + $quantity;
        $this->assertStock($product, $newQuantity);

        $unitPrice = $product->priceFor($variant) + $product->customizationPrice($customizations);

        $item = $existing ?? new CartItem([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'options_hash' => $optionsHash,
        ]);

        $item->quantity = $newQuantity;
        $item->unit_price = $unitPrice;
        $item->customizations = $this->normalizeCustomizations($product, $customizations);
        $item->special_instructions = $instructions;
        $item->save();

        $cart->shop_id = $product->shop_id;
        $cart->save();

        return $item->fresh(['product', 'variant']);
    }

    public function updateQuantity(Request $request, CartItem $item, int $quantity): void
    {
        $cart = $this->currentCart($request, false);
        if (! $cart || (int) $item->cart_id !== (int) $cart->id) {
            abort(403);
        }

        if ($quantity <= 0) {
            $item->delete();
            $this->resetShopIfEmpty($cart);

            return;
        }

        $item->load('product');
        $this->assertStock($item->product, $quantity);
        $item->quantity = $quantity;
        $item->save();
    }

    public function remove(Request $request, CartItem $item): void
    {
        $cart = $this->currentCart($request, false);
        if (! $cart || (int) $item->cart_id !== (int) $cart->id) {
            abort(403);
        }

        $item->delete();
        $this->resetShopIfEmpty($cart);
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->shop_id = null;
        $cart->save();
    }

    public function assertStock(Product $product, int $quantity): void
    {
        if ($quantity > (int) $product->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$product->stock_quantity} of {$product->name} are available.",
            ]);
        }
    }

    private function resetShopIfEmpty(Cart $cart): void
    {
        if (! $cart->items()->exists()) {
            $cart->shop_id = null;
            $cart->save();
        }
    }

    private function optionsHash(?ProductVariant $variant, array $customizations): string
    {
        $normalized = collect($customizations)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->sortKeys()
            ->all();

        return hash('sha256', json_encode([
            'variant' => $variant?->id,
            'options' => $normalized,
        ]));
    }

    private function normalizeCustomizations(Product $product, array $selected): array
    {
        $result = [];

        foreach ($product->customization_options ?? [] as $group) {
            $key = $group['key'] ?? null;
            if (! $key) {
                continue;
            }

            $value = Arr::get($selected, $key);
            if ($value === null || $value === '') {
                continue;
            }

            $option = collect($group['options'] ?? [])->firstWhere('value', $value);
            if (! $option) {
                throw ValidationException::withMessages([
                    'customizations.'.$key => 'Please choose a valid '.strtolower($group['label'] ?? $key).'.',
                ]);
            }

            $result[$key] = [
                'group' => $group['label'] ?? $key,
                'value' => $value,
                'label' => $option['label'] ?? $value,
                'price' => (float) ($option['price'] ?? 0),
            ];
        }

        foreach (($product->customization_options ?? []) as $group) {
            if (($group['required'] ?? false) && ! isset($result[$group['key'] ?? ''])) {
                throw ValidationException::withMessages([
                    'customizations.'.($group['key'] ?? '') => ($group['label'] ?? 'This option').' is required.',
                ]);
            }
        }

        return $result;
    }
}
