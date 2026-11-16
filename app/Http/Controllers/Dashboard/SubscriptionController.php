<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\ShopSubscriptionService;
use App\Services\XenditGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class SubscriptionController extends Controller
{
    public function show(Request $request, ShopSubscriptionService $subscriptions, XenditGateway $gateway): View
    {
        $shop = $request->user()->shop;
        abort_unless($shop, 404);

        $subscription = $shop->subscription()->first();
        if ($request->boolean('checkout_return') && $subscription) {
            $subscriptions->syncFromProvider($subscription);
        }

        $subscription = $shop->subscription()->with(['payments' => fn ($query) => $query->latest()->limit(5)])->first();

        return view('dashboard.subscription', [
            'shop' => $shop,
            'subscription' => $subscription,
            'latestPayment' => $subscription?->payments->first(),
            'monthlyPrice' => $subscriptions->monthlyPrice(),
            'hasAccess' => $subscriptions->hasAccess($shop),
            'checkoutConfigured' => $gateway->isConfigured(),
        ]);
    }

    public function checkout(Request $request, ShopSubscriptionService $subscriptions): RedirectResponse
    {
        $shop = $request->user()->shop;
        abort_unless($shop, 404);

        if ($subscriptions->monthlyPrice() === null) {
            return redirect()->route('dashboard.subscription.show')
                ->with('warning', 'Subscription pricing has not been configured. You will not be charged.');
        }

        try {
            $payment = $subscriptions->startCheckout($shop);

            return redirect()->away($payment->checkout_url);
        } catch (RuntimeException|ConnectionException $exception) {
            report($exception);

            return redirect()->route('dashboard.subscription.show')
                ->with('error', 'We could not start the subscription checkout. Please try again later.');
        }
    }
}
