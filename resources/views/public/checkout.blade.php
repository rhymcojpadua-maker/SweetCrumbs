@extends('layouts.public')

@section('title', 'Checkout — SweetCrumbs')

@section('content')
    <div class="container-page py-12" x-data="{
        type: @js($defaultType),
        dates: @js($datesByType),
        date: '',
        time: '',
        subtotal: {{ (float) $cart->subtotal() }},
        deliveryFee: {{ (float) ($settings?->delivery_fee ?? 0) }},
        freeThreshold: {{ (float) ($settings?->free_delivery_threshold ?? 0) }},
        get dateOptions() { return this.dates[this.type] ?? [] },
        get slots() { const entry = this.dateOptions.find((item) => item.value === this.date); return entry ? entry.slots : [] },
        get delivery() { return this.type !== 'delivery' ? 0 : (this.freeThreshold > 0 && this.subtotal >= this.freeThreshold ? 0 : this.deliveryFee) },
        get total() { return this.subtotal + this.delivery },
        changeType() { this.date = ''; this.time = '' }
    }">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">Checkout</p>
                <h1 class="mt-3 font-display text-4xl font-semibold tracking-tight sm:text-5xl">A few details, then it is in the baker's hands.</h1>
            </div>
            <a href="{{ route('cart.index') }}" class="nav-link">Back to basket</a>
        </div>

        @if (! $paymongoConfigured)
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-semibold">Online payment is not configured on this installation.</p>
                <p class="mt-1">You can still place the order. It will be saved as awaiting payment, and the bakery will not begin until payment is verified.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('checkout.store') }}" class="mt-8 grid gap-8 lg:grid-cols-[1fr_23rem]">
            @csrf

            <div class="space-y-6">
                <section class="card p-6">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="font-display text-2xl font-semibold">Your details</h2>
                        <span class="badge bg-parchment text-cocoa ring-ink/10">Step 1</span>
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="customer_name" class="label">Full name</label>
                            <input type="text" name="customer_name" id="customer_name" value="{{ old('customer_name', auth()->user()?->name) }}" required class="input">
                        </div>
                        <div>
                            <label for="customer_email" class="label">Email</label>
                            <input type="email" name="customer_email" id="customer_email" value="{{ old('customer_email', auth()->user()?->email) }}" required class="input">
                        </div>
                        <div>
                            <label for="customer_phone" class="label">Phone</label>
                            <input type="tel" name="customer_phone" id="customer_phone" value="{{ old('customer_phone', auth()->user()?->phone) }}" required class="input" placeholder="09xx xxx xxxx">
                        </div>
                    </div>
                </section>

                <section class="card p-6">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="font-display text-2xl font-semibold">Collection or delivery</h2>
                        <span class="badge bg-parchment text-cocoa ring-ink/10">Step 2</span>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @if ($settings?->pickup_enabled)
                            <label class="cursor-pointer rounded-2xl border border-ink/12 bg-white p-4 transition has-[:checked]:border-rose has-[:checked]:bg-blush/30">
                                <span class="flex items-start gap-3">
                                    <input type="radio" name="fulfillment_type" value="pickup" x-model="type" @change="changeType()" class="mt-1 border-ink/20 text-rose focus:ring-rose">
                                    <span>
                                        <span class="block font-semibold">Pickup</span>
                                        <span class="mt-1 block text-xs leading-5 text-cocoa">Collect from {{ $shop->address }}, {{ $shop->city }}.</span>
                                    </span>
                                </span>
                            </label>
                        @endif

                        @if ($settings?->delivery_enabled)
                            <label class="cursor-pointer rounded-2xl border border-ink/12 bg-white p-4 transition has-[:checked]:border-rose has-[:checked]:bg-blush/30">
                                <span class="flex items-start gap-3">
                                    <input type="radio" name="fulfillment_type" value="delivery" x-model="type" @change="changeType()" class="mt-1 border-ink/20 text-rose focus:ring-rose">
                                    <span>
                                        <span class="block font-semibold">Delivery</span>
                                        <span class="mt-1 block text-xs leading-5 text-cocoa">Delivered by the bakery. Fee calculated from their settings.</span>
                                    </span>
                                </span>
                            </label>
                        @endif
                    </div>

                    <div x-cloak x-show="type === 'delivery'" class="mt-4">
                        <label for="delivery_address" class="label">Delivery address</label>
                        <textarea name="delivery_address" id="delivery_address" rows="3" class="input" placeholder="House or unit, street, barangay, city">{{ old('delivery_address') }}</textarea>
                    </div>

                    @if ($settings?->pickup_instructions && $settings?->pickup_enabled)
                        <p x-show="type === 'pickup'" class="mt-4 rounded-xl bg-parchment/60 px-4 py-3 text-xs leading-5 text-cocoa">{{ $settings->pickup_instructions }}</p>
                    @endif
                    @if ($settings?->delivery_notes && $settings?->delivery_enabled)
                        <p x-show="type === 'delivery'" class="mt-4 rounded-xl bg-parchment/60 px-4 py-3 text-xs leading-5 text-cocoa">{{ $settings->delivery_notes }}</p>
                    @endif
                </section>

                <section class="card p-6">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="font-display text-2xl font-semibold">When should it be ready?</h2>
                        <span class="badge bg-parchment text-cocoa ring-ink/10">Step 3</span>
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="scheduled_date" class="label">Date</label>
                            <select name="scheduled_date" id="scheduled_date" x-model="date" @change="time = ''" required class="input">
                                <option value="">Choose a date</option>
                                <template x-for="option in dateOptions" :key="option.value">
                                    <option :value="option.value" x-text="option.long_label"></option>
                                </template>
                            </select>
                            <p x-show="dateOptions.length === 0" class="mt-2 text-xs font-medium text-rose">No dates are available for this method. Please try the other option or contact the bakery.</p>
                        </div>
                        <div>
                            <label for="scheduled_time" class="label">Time</label>
                            <select name="scheduled_time" id="scheduled_time" x-model="time" required class="input" :disabled="!date">
                                <option value="">Choose a time</option>
                                <template x-for="slot in slots" :key="slot">
                                    <option :value="slot" x-text="slot"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="notes" class="label">Notes for the bakery (optional)</label>
                        <textarea name="notes" id="notes" rows="3" maxlength="1000" class="input" placeholder="Allergies, gate codes, or a note about the occasion.">{{ old('notes') }}</textarea>
                    </div>
                </section>
            </div>

            <aside>
                <div class="sticky top-28 space-y-5">
                    <div class="card p-6">
                        <h2 class="font-display text-2xl font-semibold">Your order</h2>
                        <div class="mt-4 flex items-center gap-3 rounded-xl bg-parchment/50 p-3">
                            <img src="{{ $shop->logoUrl() }}" alt="" class="h-9 w-9 rounded-full object-cover">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">{{ $shop->name }}</p>
                                <p class="truncate text-xs text-cocoa">{{ $shop->city }}</p>
                            </div>
                        </div>

                        <ul class="mt-5 space-y-3">
                            @foreach ($cart->items as $item)
                                <li class="flex items-start justify-between gap-3 text-sm">
                                    <div class="min-w-0">
                                        <p class="truncate font-medium">{{ $item->quantity }} × {{ $item->product->name }}</p>
                                        <p class="text-xs text-cocoa">{{ $item->variant?->name ?: 'Standard size' }}</p>
                                    </div>
                                    <span class="shrink-0 font-semibold">₱{{ number_format($item->lineTotal(), 0) }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <dl class="mt-5 space-y-2.5 border-t border-ink/8 pt-4 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-cocoa">Subtotal</dt>
                                <dd class="font-medium">₱{{ number_format($cart->subtotal(), 0) }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-cocoa">Delivery</dt>
                                <dd class="font-medium"><span x-text="delivery > 0 ? '₱' + delivery.toLocaleString() : 'Free'"></span></dd>
                            </div>
                            <div class="flex justify-between gap-3 border-t border-ink/8 pt-3 text-base">
                                <dt class="font-semibold">Total</dt>
                                <dd class="font-display text-2xl font-semibold">₱<span x-text="total.toLocaleString()"></span></dd>
                            </div>
                        </dl>

                        <label class="mt-5 flex items-start gap-3 text-xs leading-5 text-cocoa">
                            <input type="checkbox" name="terms" value="1" required class="mt-0.5 rounded border-ink/20 text-rose focus:ring-rose">
                            <span>I agree to the <a href="{{ route('terms') }}" class="font-semibold text-rose hover:underline">terms of service</a> and understand that each bakery fulfils its own order.</span>
                        </label>

                        <button type="submit" class="mt-5 btn-primary w-full" :disabled="!date || !time">
                            {{ $paymongoConfigured ? 'Continue to secure payment' : 'Place order' }}
                        </button>
                        <p class="mt-3 text-center text-xs text-cocoa">Payments are verified by PayMongo before the bakery begins.</p>
                    </div>
                </div>
            </aside>
        </form>
    </div>
@endsection
