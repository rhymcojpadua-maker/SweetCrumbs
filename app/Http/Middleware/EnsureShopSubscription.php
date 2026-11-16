<?php

namespace App\Http\Middleware;

use App\Services\ShopSubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureShopSubscription
{
    public function __construct(private readonly ShopSubscriptionService $subscriptions) {}

    public function handle(Request $request, Closure $next): Response
    {
        $shop = $request->user()?->shop;

        if (! $shop || $this->subscriptions->hasAccess($shop)) {
            return $next($request);
        }

        return redirect()->route('dashboard.subscription.show')
            ->with('warning', 'Your bakery subscription is required to continue using the dashboard.');
    }
}
