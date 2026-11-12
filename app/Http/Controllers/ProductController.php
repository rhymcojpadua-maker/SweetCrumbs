<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        $product->load(['shop.settings', 'category', 'images', 'variants', 'reviews.customer']);
        abort_unless($product->is_active && $product->shop?->isActive(), 404);

        $reviews = $product->reviews()
            ->with('customer')
            ->where('is_visible', true)
            ->latest()
            ->take(8)
            ->get();

        $related = Product::query()
            ->with(['images', 'variants', 'shop'])
            ->where('shop_id', $product->shop_id)
            ->where('is_active', true)
            ->whereKeyNot($product->id)
            ->when($product->category_id, fn ($query) => $query->where('category_id', $product->category_id))
            ->latest()
            ->take(4)
            ->get();

        if ($related->count() < 4) {
            $related = Product::query()
                ->with(['images', 'variants', 'shop'])
                ->where('shop_id', $product->shop_id)
                ->where('is_active', true)
                ->whereKeyNot($product->id)
                ->latest()
                ->take(4)
                ->get();
        }

        $averageRating = round((float) $reviews->avg('rating'), 1);

        return view('public.product', compact('product', 'reviews', 'related', 'averageRating'));
    }
}
