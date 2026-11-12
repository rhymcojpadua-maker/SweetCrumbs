<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();

        $shops = Shop::query()
            ->active()
            ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $builder) use ($search) {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('city', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            }))
            ->orderByDesc('is_featured')
            ->orderByDesc('rating')
            ->paginate(12)
            ->withQueryString();

        return view('public.shops', compact('shops', 'search'));
    }

    public function show(Request $request, Shop $shop): View
    {
        abort_unless($shop->isActive(), 404);

        $shop->load('settings');

        $products = Product::query()
            ->with(['images', 'variants', 'category'])
            ->where('shop_id', $shop->id)
            ->where('is_active', true)
            ->when($request->filled('category'), fn (Builder $query) => $query->whereHas('category', fn (Builder $category) => $category->where('slug', $request->string('category'))))
            ->when($request->filled('q'), function (Builder $query) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(fn (Builder $builder) => $builder->where('name', 'like', $term)->orWhere('description', 'like', $term));
            })
            ->when($request->input('sort') === 'price_low', fn (Builder $query) => $query->orderBy('base_price'))
            ->when($request->input('sort') === 'price_high', fn (Builder $query) => $query->orderByDesc('base_price'))
            ->when(! in_array($request->input('sort'), ['price_low', 'price_high'], true), fn (Builder $query) => $query->orderByDesc('is_featured')->latest())
            ->paginate(9)
            ->withQueryString();

        $categories = $shop->categories()->where('is_active', true)->withCount('products')->orderBy('sort_order')->get();

        $reviews = $shop->reviews()
            ->with('customer')
            ->where('is_visible', true)
            ->latest()
            ->take(6)
            ->get();

        $reviewCount = $shop->reviews()->where('is_visible', true)->count();

        $ratingBreakdown = collect(range(5, 1))->mapWithKeys(fn ($rating) => [
            $rating => $shop->reviews()->where('is_visible', true)->where('rating', $rating)->count(),
        ]);

        return view('public.shop', compact('shop', 'products', 'categories', 'reviews', 'ratingBreakdown', 'reviewCount'));
    }
}
