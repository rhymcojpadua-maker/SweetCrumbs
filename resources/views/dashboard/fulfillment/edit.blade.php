@extends('layouts.dashboard')

@section('title', 'Fulfillment')
@section('eyebrow', 'Settings')
@section('heading', 'Fulfillment &amp; schedule')

@section('content')
    <form method="POST" action="{{ route('dashboard.fulfillment.update') }}" class="grid gap-5 xl:grid-cols-[1fr_21rem]">
        @csrf
        @method('PATCH')

        <div class="space-y-5">
            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-2xl font-semibold">How customers receive cakes</h2>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <label class="cursor-pointer rounded-xl border border-ink/12 bg-white p-4 has-[:checked]:border-rose has-[:checked]:bg-blush/30">
                        <span class="flex items-start gap-3">
                            <input type="checkbox" name="pickup_enabled" value="1" @checked(old('pickup_enabled', $settings?->pickup_enabled)) class="mt-1 rounded border-ink/20 text-rose focus:ring-rose">
                            <span>
                                <span class="block font-semibold">Pickup</span>
                                <span class="mt-1 block text-xs leading-5 text-cocoa">Customers collect from {{ $shop->address }}.</span>
                            </span>
                        </span>
                    </label>
                    <label class="cursor-pointer rounded-xl border border-ink/12 bg-white p-4 has-[:checked]:border-rose has-[:checked]:bg-blush/30">
                        <span class="flex items-start gap-3">
                            <input type="checkbox" name="delivery_enabled" value="1" @checked(old('delivery_enabled', $settings?->delivery_enabled)) class="mt-1 rounded border-ink/20 text-rose focus:ring-rose">
                            <span>
                                <span class="block font-semibold">Delivery</span>
                                <span class="mt-1 block text-xs leading-5 text-cocoa">You deliver within an area you control.</span>
                            </span>
                        </span>
                    </label>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="delivery_fee" class="label">Delivery fee (₱)</label>
                        <input type="number" step="0.01" min="0" max="100000" name="delivery_fee" id="delivery_fee" value="{{ old('delivery_fee', $settings?->delivery_fee ?? 0) }}" required class="input">
                    </div>
                    <div>
                        <label for="free_delivery_threshold" class="label">Free delivery over (₱)</label>
                        <input type="number" step="0.01" min="0" max="1000000" name="free_delivery_threshold" id="free_delivery_threshold" value="{{ old('free_delivery_threshold', $settings?->free_delivery_threshold) }}" class="input" placeholder="Leave blank to always charge">
                    </div>
                    <div>
                        <label for="pickup_instructions" class="label">Pickup instructions</label>
                        <textarea name="pickup_instructions" id="pickup_instructions" rows="3" maxlength="1000" class="input">{{ old('pickup_instructions', $settings?->pickup_instructions) }}</textarea>
                    </div>
                    <div>
                        <label for="delivery_notes" class="label">Delivery notes</label>
                        <textarea name="delivery_notes" id="delivery_notes" rows="3" maxlength="1000" class="input">{{ old('delivery_notes', $settings?->delivery_notes) }}</textarea>
                    </div>
                </div>
            </section>

            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-2xl font-semibold">Days you accept orders</h2>
                <p class="mt-1 text-xs text-cocoa">These control which dates appear at checkout for each method.</p>

                <div class="mt-5 grid gap-6 sm:grid-cols-2">
                    <div>
                        <p class="label">Pickup days</p>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            @foreach ($days as $value => $label)
                                <label class="flex items-center gap-2 text-sm text-cocoa">
                                    <input type="checkbox" name="pickup_days[]" value="{{ $value }}" @checked(in_array($value, old('pickup_days', $settings?->pickup_days ?? []))) class="rounded border-ink/20 text-rose focus:ring-rose">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <p class="label">Delivery days</p>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            @foreach ($days as $value => $label)
                                <label class="flex items-center gap-2 text-sm text-cocoa">
                                    <input type="checkbox" name="delivery_days[]" value="{{ $value }}" @checked(in_array($value, old('delivery_days', $settings?->delivery_days ?? []))) class="rounded border-ink/20 text-rose focus:ring-rose">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="time_slots" class="label">Time slots (24-hour, comma separated)</label>
                        <input type="text" name="time_slots" id="time_slots" value="{{ old('time_slots', implode(', ', $settings?->time_slots ?? [])) }}" required class="input" placeholder="09:00, 11:00, 14:00, 16:00">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="unavailable_dates" class="label">Unavailable dates (YYYY-MM-DD, comma separated)</label>
                        <textarea name="unavailable_dates" id="unavailable_dates" rows="2" class="input" placeholder="2026-12-25, 2026-01-01">{{ old('unavailable_dates', implode(', ', $settings?->unavailable_dates ?? [])) }}</textarea>
                    </div>
                    <div>
                        <label for="max_orders_per_day" class="label">Max orders per day</label>
                        <input type="number" min="1" max="500" name="max_orders_per_day" id="max_orders_per_day" value="{{ old('max_orders_per_day', $settings?->max_orders_per_day ?? 6) }}" required class="input">
                    </div>
                    <div>
                        <label for="advance_notice_hours" class="label">Advance notice (hours)</label>
                        <input type="number" min="0" max="720" name="advance_notice_hours" id="advance_notice_hours" value="{{ old('advance_notice_hours', $settings?->advance_notice_hours ?? 48) }}" required class="input">
                    </div>
                </div>
            </section>
        </div>

        <aside class="space-y-5">
            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-xl font-semibold">Save schedule</h2>
                <p class="mt-2 text-sm leading-6 text-cocoa">Changes take effect on the next checkout. Existing orders keep their agreed schedule.</p>
                <button type="submit" class="mt-4 btn-primary w-full">Save settings</button>
            </section>

            <section class="card-flat p-5 text-sm leading-6 text-cocoa">
                <p class="font-semibold text-ink">Capacity is checked server-side</p>
                <p class="mt-1">Once a day reaches its order limit, that date disappears from checkout automatically.</p>
            </section>
        </aside>
    </form>
@endsection
