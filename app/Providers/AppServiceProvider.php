<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shop;
use App\Policies\CategoryPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ShopPolicy;
use App\Services\CartService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        Gate::policy(Shop::class, ShopPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('checkout', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(180)->by($request->ip());
        });

        RateLimiter::for('tracking', function (Request $request) {
            return Limit::perMinute(15)->by($request->ip());
        });

        View::composer(['layouts.public', 'layouts.dashboard', 'layouts.admin', 'dashboard.notifications.index'], function ($view) {
            $cart = null;
            $cartCount = 0;

            try {
                if (! request()->routeIs('webhooks.*')) {
                    $cart = app(CartService::class)->currentCart(request(), false);
                    $cartCount = $cart?->itemCount() ?? 0;
                }
            } catch (\Throwable) {
                $cart = null;
            }

            $view->with([
                'navCart' => $cart,
                'navCartCount' => $cartCount,
                'unreadNotifications' => auth()->check()
                    ? auth()->user()->appNotifications()->whereNull('read_at')->latest()->take(6)->get()
                    : collect(),
                'unreadNotificationCount' => auth()->check()
                    ? auth()->user()->appNotifications()->whereNull('read_at')->count()
                    : 0,
            ]);
        });
    }
}
