<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\ScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FulfillmentController extends Controller
{
    public function edit(Request $request, ScheduleService $schedule): View
    {
        $shop = $request->user()->shop->load('settings');

        return view('dashboard.fulfillment.edit', [
            'shop' => $shop,
            'settings' => $shop->settings,
            'days' => $schedule->daysForSettings(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $shop = $request->user()->shop->load('settings');

        $data = $request->validate([
            'pickup_enabled' => ['nullable', 'boolean'],
            'delivery_enabled' => ['nullable', 'boolean'],
            'pickup_days' => ['nullable', 'array'],
            'pickup_days.*' => ['integer', 'between:1,7'],
            'delivery_days' => ['nullable', 'array'],
            'delivery_days.*' => ['integer', 'between:1,7'],
            'time_slots' => ['required', 'string', 'max:1000'],
            'unavailable_dates' => ['nullable', 'string', 'max:2000'],
            'max_orders_per_day' => ['required', 'integer', 'min:1', 'max:500'],
            'advance_notice_hours' => ['required', 'integer', 'min:0', 'max:720'],
            'delivery_fee' => ['required', 'numeric', 'min:0', 'max:100000'],
            'free_delivery_threshold' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'pickup_instructions' => ['nullable', 'string', 'max:1000'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $slots = collect(explode(',', $data['time_slots']))
            ->map(fn ($slot) => trim($slot))
            ->filter(fn ($slot) => preg_match('/^\d{2}:\d{2}$/', $slot))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $unavailable = collect(preg_split('/[\s,]+/', (string) ($data['unavailable_dates'] ?? '')))
            ->map(fn ($date) => trim($date))
            ->filter(fn ($date) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
            ->unique()
            ->values()
            ->all();

        $settings = $shop->settings ?? $shop->settings()->create();
        $settings->update([
            'pickup_enabled' => $request->boolean('pickup_enabled'),
            'delivery_enabled' => $request->boolean('delivery_enabled'),
            'pickup_days' => array_values($data['pickup_days'] ?? []),
            'delivery_days' => array_values($data['delivery_days'] ?? []),
            'time_slots' => $slots,
            'unavailable_dates' => $unavailable,
            'max_orders_per_day' => (int) $data['max_orders_per_day'],
            'advance_notice_hours' => (int) $data['advance_notice_hours'],
            'delivery_fee' => $data['delivery_fee'],
            'free_delivery_threshold' => $data['free_delivery_threshold'] ?? null,
            'pickup_instructions' => $data['pickup_instructions'] ?? null,
            'delivery_notes' => $data['delivery_notes'] ?? null,
        ]);

        if (! $request->boolean('pickup_enabled') && ! $request->boolean('delivery_enabled')) {
            return back()->with('warning', 'Settings saved, but at least one fulfillment method should be enabled to accept orders.');
        }

        return back()->with('success', 'Fulfillment settings saved.');
    }
}
