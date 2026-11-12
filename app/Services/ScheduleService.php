<?php

namespace App\Services;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ScheduleService
{
    public function availableDates(Shop $shop, FulfillmentType $type, int $productLeadHours = 0): array
    {
        $settings = $shop->settings;
        $enabledDays = $type === FulfillmentType::Pickup
            ? ($settings?->pickup_days ?? [])
            : ($settings?->delivery_days ?? []);

        if (! $settings || ($type === FulfillmentType::Pickup && ! $settings->pickup_enabled) || ($type === FulfillmentType::Delivery && ! $settings->delivery_enabled)) {
            return [];
        }

        $leadHours = max((int) $settings->advance_notice_hours, $productLeadHours);
        $earliest = now()->addHours($leadHours);
        $unavailable = collect($settings->unavailable_dates ?? [])->filter()->values();
        $slots = collect($settings->time_slots ?? [])->filter()->values();
        $dates = [];

        for ($i = 0; $i < (int) config('cakelle.schedule_days_ahead', 90); $i++) {
            $date = now()->startOfDay()->addDays($i);
            if (! in_array($date->dayOfWeekIso, array_map('intval', $enabledDays), true)) {
                continue;
            }

            if ($unavailable->contains($date->toDateString())) {
                continue;
            }

            if ($this->ordersCount($shop, $date->toDateString()) >= (int) $settings->max_orders_per_day) {
                continue;
            }

            $availableSlots = $slots->filter(function (string $slot) use ($date, $earliest) {
                return $date->copy()->setTimeFromTimeString($slot)->greaterThan($earliest);
            })->values()->all();

            if ($availableSlots === []) {
                continue;
            }

            $dates[] = [
                'value' => $date->toDateString(),
                'label' => $date->format('D, M j'),
                'long_label' => $date->format('l, F j, Y'),
                'slots' => $availableSlots,
            ];
        }

        return $dates;
    }

    public function validateSelection(Shop $shop, FulfillmentType $type, string $date, string $time, int $productLeadHours = 0): void
    {
        $available = collect($this->availableDates($shop, $type, $productLeadHours));
        $selectedDate = $available->firstWhere('value', $date);

        if (! $selectedDate) {
            throw ValidationException::withMessages([
                'scheduled_date' => ucfirst($type->value).' is not available on that date. Please choose another date.',
            ]);
        }

        if (! in_array($time, $selectedDate['slots'], true)) {
            throw ValidationException::withMessages([
                'scheduled_time' => 'That time slot is no longer available. Please choose another time.',
            ]);
        }
    }

    public function slotsForDate(Shop $shop, FulfillmentType $type, string $date, int $productLeadHours = 0): array
    {
        return collect($this->availableDates($shop, $type, $productLeadHours))
            ->firstWhere('value', $date)['slots'] ?? [];
    }

    public function ordersCount(Shop $shop, string $date): int
    {
        return Order::query()
            ->where('shop_id', $shop->id)
            ->whereDate('scheduled_date', $date)
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Rejected->value])
            ->count();
    }

    public function daysForSettings(): array
    {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];
    }

    public function nextAvailableDate(Shop $shop, FulfillmentType $type, int $leadHours = 0): ?Carbon
    {
        $dates = $this->availableDates($shop, $type, $leadHours);

        return $dates === [] ? null : Carbon::parse($dates[0]['value']);
    }

    public function capacitySummary(Shop $shop, string $date): array
    {
        $used = $this->ordersCount($shop, $date);
        $max = (int) ($shop->settings?->max_orders_per_day ?? 0);

        return [
            'used' => $used,
            'max' => $max,
            'remaining' => max(0, $max - $used),
            'percent' => $max > 0 ? min(100, (int) round(($used / $max) * 100)) : 100,
        ];
    }

    public function upcomingAvailableDates(Shop $shop, int $limit = 14): Collection
    {
        $settings = $shop->settings;
        $type = $settings?->pickup_enabled ? FulfillmentType::Pickup : FulfillmentType::Delivery;
        $dates = collect($this->availableDates($shop, $type))->take($limit);

        return $dates->map(function (array $date) {
            return [
                'date' => Carbon::parse($date['value']),
                'slots' => $date['slots'],
            ];
        });
    }
}
