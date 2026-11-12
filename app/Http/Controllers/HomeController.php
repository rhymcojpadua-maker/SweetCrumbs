<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use App\Services\CmsService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(CmsService $cms): View
    {
        $categories = Category::marketplace(6);

        $featuredProducts = Product::query()
            ->with(['shop', 'category', 'images', 'variants'])
            ->where('is_active', true)
            ->where('is_featured', true)
            ->whereHas('shop', fn ($query) => $query->active())
            ->latest()
            ->take(8)
            ->get();

        if ($featuredProducts->count() < 4) {
            $featuredProducts = Product::query()
                ->with(['shop', 'category', 'images', 'variants'])
                ->where('is_active', true)
                ->whereHas('shop', fn ($query) => $query->active())
                ->latest()
                ->take(8)
                ->get();
        }

        $featuredShops = Shop::query()
            ->active()
            ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
            ->orderByDesc('is_featured')
            ->orderByDesc('rating')
            ->take(4)
            ->get();

        $testimonials = Review::query()
            ->with(['customer', 'shop'])
            ->where('is_visible', true)
            ->where('rating', '>=', 4)
            ->latest()
            ->take(3)
            ->get();

        $stats = [
            'bakeries' => Shop::query()->active()->count(),
            'cakes' => Product::query()->where('is_active', true)->whereHas('shop', fn ($query) => $query->active())->count(),
            'orders' => Order::query()->where('status', 'completed')->count(),
            'rating' => round((float) Review::query()->where('is_visible', true)->avg('rating'), 1),
        ];

        return view('public.home', [
            'cms' => $cms->homepage(),
            'cmsFaqs' => $cms->faqs()->take(4),
            'cmsTestimonials' => $cms->testimonials(),
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
            'featuredShops' => $featuredShops,
            'testimonials' => $testimonials,
            'stats' => $stats,
        ]);
    }
}
