<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\ActivityLogger;
use App\Services\ShopSubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $settings = [
            'platform_name' => PlatformSetting::get('platform_name', 'SweetCrumbs'),
            'support_email' => PlatformSetting::get('support_email', config('cakelle.support_email')),
            'support_phone' => PlatformSetting::get('support_phone', config('cakelle.support_phone')),
            'announcement' => PlatformSetting::get('announcement', ''),
            'announcement_enabled' => PlatformSetting::get('announcement_enabled', false),
            'bakery_subscription_monthly_price' => PlatformSetting::get('bakery_subscription_monthly_price'),
        ];

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request, ActivityLogger $logger, ShopSubscriptionService $subscriptions): RedirectResponse
    {
        $data = $request->validate([
            'platform_name' => ['required', 'string', 'max:80'],
            'support_email' => ['required', 'email', 'max:190'],
            'support_phone' => ['required', 'string', 'max:30'],
            'announcement' => ['nullable', 'string', 'max:300'],
            'announcement_enabled' => ['nullable', 'boolean'],
            'bakery_subscription_monthly_price' => ['nullable', 'numeric', 'decimal:0,2', 'min:1', 'max:99999999.99'],
        ]);

        $previousSubscriptionPrice = $subscriptions->monthlyPrice();
        $newSubscriptionPrice = isset($data['bakery_subscription_monthly_price'])
            ? round((float) $data['bakery_subscription_monthly_price'], 2)
            : null;

        foreach (['platform_name', 'support_email', 'support_phone', 'announcement'] as $key) {
            PlatformSetting::set($key, $data[$key] ?? '');
        }
        PlatformSetting::set('announcement_enabled', $request->boolean('announcement_enabled'));
        PlatformSetting::set('bakery_subscription_monthly_price', $newSubscriptionPrice);

        if ($previousSubscriptionPrice === null && $newSubscriptionPrice !== null) {
            $subscriptions->grantExistingShopsGracePeriod();
        }

        $logger->log('settings.updated', 'Platform settings updated');

        return back()->with('success', 'Platform settings saved.');
    }
}
