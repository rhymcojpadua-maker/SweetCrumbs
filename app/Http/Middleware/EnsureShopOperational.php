<?php

namespace App\Http\Middleware;

use App\Enums\ShopStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureShopOperational
{
    public function handle(Request $request, Closure $next): Response
    {
        $shop = $request->user()?->shop;

        if (! $shop) {
            return redirect()->route('shop.register')->with('status', 'Complete your bakery application to open the dashboard.');
        }

        if (in_array($shop->status, [ShopStatus::Pending, ShopStatus::Rejected], true)) {
            return redirect()->route('dashboard.pending');
        }

        if ($shop->status === ShopStatus::Suspended) {
            return redirect()->route('dashboard.suspended');
        }

        view()->share('currentShop', $shop);

        return $next($request);
    }
}
