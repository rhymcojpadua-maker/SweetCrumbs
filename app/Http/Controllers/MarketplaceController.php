<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'fulfillment' => ['nullable', 'in:pickup,delivery'],
            'sort' => ['nullable', 'in:recommended,newest,price_low,price_high,rating'],
        ]);

        $query = Product::query()
            ->with(['shop', 'category', 'images', 'variants'])
            ->where('is_active', true)
            ->whereHas('shop', fn (Builder $shop) => $shop->active());

        if (! empty($filters['q'])) {
            $term = '%'.trim($filters['q']).'%';
            $query->where(function (Builder $builder) use ($term) {
                $builder->where('name', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhereHas('shop', fn (Builder $shop) => $shop->where('name', 'like', $term));
            });
        }

        if (! empty($filters['category'])) {
            $query->whereHas('category', fn (Builder $category) => $category->where('slug', $filters['category']));
        }

        if (! empty($filters['city'])) {
            $query->whereHas('shop', fn (Builder $shop) => $shop->where('city', $filters['city']));
        }

        if (isset($filters['min_price'])) {
            $query->where('base_price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('base_price', '<=', $filters['max_price']);
        }

        if (! empty($filters['fulfillment'])) {
            $query->whereHas('shop.settings', function (Builder $settings) use ($filters) {
                $settings->where($filters['fulfillment'].'_enabled', true);
            });
        }

        match ($filters['sort'] ?? 'recommended') {
            'newest' => $query->latest(),
            'price_low' => $query->orderBy('base_price'),
            'price_high' => $query->orderByDesc('base_price'),
            'rating' => $query->orderByDesc(Shop::select('rating')->whereColumn('shops.id', 'products.shop_id')),
            default => $query->orderByDesc('is_featured')->latest(),
        };

        $products = $query->paginate(12)->withQueryString();
        $categories = Category::marketplace();
        $cities = Shop::query()->active()->distinct()->orderBy('city')->pluck('city');

        return view('public.marketplace', [
            'products' => $products,
            'categories' => $categories,
            'cities' => $cities,
            'filters' => $filters,
        ]);
    }
}
