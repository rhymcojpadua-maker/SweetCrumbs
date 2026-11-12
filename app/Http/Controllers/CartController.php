<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function index(Request $request): View
    {
        $cart = $this->cartService->currentCart($request, false);

        return view('public.cart', compact('cart'));
    }

    public function add(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'customizations' => ['nullable', 'array'],
            'customizations.*' => ['nullable', 'string', 'max:100'],
            'special_instructions' => ['nullable', 'string', 'max:500'],
            'buy_now' => ['nullable', 'boolean'],
        ]);

        $product = Product::query()
            ->with(['shop.settings', 'variants'])
            ->where('is_active', true)
            ->findOrFail($data['product_id']);

        $variant = null;
        if (! empty($data['variant_id'])) {
            $variant = ProductVariant::query()
                ->where('product_id', $product->id)
                ->findOrFail($data['variant_id']);
        }

        $item = $this->cartService->add(
            $request,
            $product,
            $variant,
            (int) $data['quantity'],
            $data['customizations'] ?? [],
            $data['special_instructions'] ?? null
        );

        $cart = $this->cartService->currentCart($request, false);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $product->name.' added to your basket.',
                'item_count' => $cart?->itemCount() ?? 0,
                'subtotal' => number_format($cart?->subtotal() ?? 0, 2),
                'item' => $item->only(['id', 'quantity', 'unit_price']),
            ]);
        }

        if ($request->boolean('buy_now')) {
            return redirect()->route('checkout.show');
        }

        return redirect()->route('cart.index')->with('success', $product->name.' added to your basket.');
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        $this->cartService->updateQuantity($request, $item, (int) $data['quantity']);

        return redirect()->route('cart.index')->with('success', 'Your basket has been updated.');
    }

    public function remove(Request $request, CartItem $item): RedirectResponse
    {
        $this->cartService->remove($request, $item);

        return redirect()->route('cart.index')->with('success', 'Item removed from your basket.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $cart = $this->cartService->currentCart($request, false);

        if ($cart) {
            $this->cartService->clear($cart);
        }

        return redirect()->route('cart.index')->with('success', 'Your basket is now empty.');
    }
}
