<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ShopStatus;
use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Services\ActivityLogger;
use App\Services\ShopSubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function applications(): View
    {
        $shops = Shop::query()
            ->with('owner')
            ->where('status', ShopStatus::Pending->value)
            ->oldest()
            ->paginate(15);

        return view('admin.applications.index', compact('shops'));
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(ShopStatus::class)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $shops = Shop::query()
            ->with('owner')
            ->withCount(['products', 'orders'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, function ($query, $term) {
                $term = '%'.trim($term).'%';
                $query->where(fn ($builder) => $builder
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('city', 'like', $term));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.shops.index', [
            'shops' => $shops,
            'filters' => $filters,
            'statuses' => ShopStatus::cases(),
        ]);
    }

    public function show(Shop $shop): View
    {
        $shop->load(['owner', 'settings']);
        $shop->loadCount(['products', 'orders', 'customers', 'reviews']);

        $recentOrders = $shop->orders()->latest()->take(8)->get();
        $revenue = (float) $shop->orders()->where('payment_status', 'paid')->sum('total');

        return view('admin.shops.show', compact('shop', 'recentOrders', 'revenue'));
    }

    public function approve(Shop $shop, ActivityLogger $logger, ShopSubscriptionService $subscriptions): RedirectResponse
    {
        $shop->update([
            'status' => ShopStatus::Active,
            'approved_at' => now(),
            'suspended_at' => null,
            'rejection_reason' => null,
        ]);
        $subscriptions->grantTrialIfNeeded($shop);

        $shop->owner->update(['is_active' => true]);

        $shop->owner->appNotifications()->create([
            'shop_id' => $shop->id,
            'type' => 'shop_approved',
            'title' => 'Your bakery is live on SweetCrumbs',
            'body' => $shop->name.' has been approved. Add your cakes and start accepting orders.',
            'data' => ['shop_id' => $shop->id],
        ]);

        $logger->log('shop.approved', 'Shop '.$shop->name.' approved', $shop);

        return redirect()->route('admin.shops.show', $shop)->with('success', 'Shop approved and published.');
    }

    public function reject(Request $request, Shop $shop, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $shop->update([
            'status' => ShopStatus::Rejected,
            'rejection_reason' => $data['reason'],
        ]);

        $shop->owner->appNotifications()->create([
            'shop_id' => $shop->id,
            'type' => 'shop_rejected',
            'title' => 'Bakery application update',
            'body' => 'Your application needs attention: '.$data['reason'],
            'data' => ['shop_id' => $shop->id],
        ]);

        $logger->log('shop.rejected', 'Shop '.$shop->name.' rejected', $shop, null, ['reason' => $data['reason']]);

        return redirect()->route('admin.shops.show', $shop)->with('success', 'Application rejected.');
    }

    public function suspend(Request $request, Shop $shop, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $shop->update([
            'status' => ShopStatus::Suspended,
            'suspended_at' => now(),
        ]);

        $shop->owner->appNotifications()->create([
            'shop_id' => $shop->id,
            'type' => 'shop_suspended',
            'title' => 'Your bakery has been suspended',
            'body' => $data['reason'] ?? 'Please contact SweetCrumbs support for more information.',
            'data' => ['shop_id' => $shop->id],
        ]);

        $logger->log('shop.suspended', 'Shop '.$shop->name.' suspended', $shop, null, ['reason' => $data['reason'] ?? null]);

        return back()->with('success', 'Shop suspended.');
    }

    public function activate(Shop $shop, ActivityLogger $logger, ShopSubscriptionService $subscriptions): RedirectResponse
    {
        $shop->update([
            'status' => ShopStatus::Active,
            'approved_at' => $shop->approved_at ?? now(),
            'suspended_at' => null,
        ]);
        $subscriptions->grantTrialIfNeeded($shop);

        $shop->owner->appNotifications()->create([
            'shop_id' => $shop->id,
            'type' => 'shop_activated',
            'title' => 'Your bakery is active again',
            'body' => $shop->name.' is live on SweetCrumbs.',
            'data' => ['shop_id' => $shop->id],
        ]);

        $logger->log('shop.activated', 'Shop '.$shop->name.' activated', $shop);

        return back()->with('success', 'Shop activated.');
    }
}
