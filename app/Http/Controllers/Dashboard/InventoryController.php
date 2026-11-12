<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $shop = $request->user()->shop;

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'stock' => ['nullable', 'in:all,low,out'],
        ]);

        $products = Product::query()
            ->where('shop_id', $shop->id)
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('name', 'like', '%'.trim($term).'%'))
            ->when(($filters['stock'] ?? null) === 'low', fn ($query) => $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->where('stock_quantity', '>', 0))
            ->when(($filters['stock'] ?? null) === 'out', fn ($query) => $query->where('stock_quantity', 0))
            ->orderBy('stock_quantity')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $movements = InventoryMovement::query()
            ->with(['product', 'user', 'order'])
            ->where('shop_id', $shop->id)
            ->latest()
            ->paginate(12, ['*'], 'movements')
            ->withQueryString();

        $summary = [
            'low' => Product::query()->where('shop_id', $shop->id)->where('is_active', true)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->where('stock_quantity', '>', 0)->count(),
            'out' => Product::query()->where('shop_id', $shop->id)->where('is_active', true)->where('stock_quantity', 0)->count(),
            'units' => (int) Product::query()->where('shop_id', $shop->id)->sum('stock_quantity'),
        ];

        return view('dashboard.inventory.index', compact('shop', 'products', 'movements', 'summary', 'filters'));
    }

    public function adjust(Request $request, Product $product, InventoryService $inventory): RedirectResponse
    {
        $shop = $request->user()->shop;
        abort_unless((int) $product->shop_id === (int) $shop->id, 404);

        $data = $request->validate([
            'new_quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $inventory->adjust($product, (int) $data['new_quantity'], $data['reason'], $request->user());

        return back()->with('success', $product->name.' stock updated.');
    }
}
