@extends('layouts.public')

@section('title', 'Order '.$order->order_number.' — Cakelle')

@section('content')
    <div class="container-page py-12">
        <div class="flex flex-wrap items-start justify-between gap-5">
            <div>
                <p class="eyebrow">Order {{ $order->order_number }}</p>
                <h1 class="mt-3 font-display text-4xl font-semibold tracking-tight sm:text-5xl">{{ $order->status->customerLabel() }}</h1>
                <p class="mt-3 text-cocoa">Placed {{ $order->placed_at?->format('M j, Y \a\t g:i A') }} with {{ $order->shop->name }}.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="badge {{ $order->status->badgeClasses() }}">{{ $order->status->label() }}</span>
                <span class="badge {{ $order->payment_status->badgeClasses() }}">{{ $order->payment_status->label() }}</span>
            </div>
        </div>

        @if ($steps !== [])
            <div class="mt-10 card overflow-hidden p-5 sm:p-6">
                <ol class="grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($steps as $step)
                        <li class="flex items-start gap-3 lg:flex-col lg:gap-2">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-bold {{ $step['state'] === 'complete' ? 'bg-emerald-600 text-white' : ($step['state'] === 'current' ? 'bg-ink text-cream' : 'bg-parchment text-cocoa') }}">
                                @if ($step['state'] === 'complete')
                                    <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0l-3.5-3.5a1 1 0 1 1 1.4-1.4l2.8 2.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" /></svg>
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </span>
                            <div>
                                <p class="text-sm font-semibold {{ $step['state'] === 'upcoming' ? 'text-cocoa' : 'text-ink' }}">{{ $step['label'] }}</p>
                                @if ($step['state'] === 'current')
                                    <p class="mt-0.5 text-xs text-rose">In progress</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        @if ($canPay)
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="font-semibold text-amber-900">This order is awaiting payment.</p>
                        <p class="mt-1 text-sm text-amber-800">Your bakery will not begin until PayMongo verifies the payment.</p>
                    </div>
                    <form method="POST" action="{{ route('payments.resume', ['order' => $order, 'token' => $order->public_token]) }}">
                        @csrf
                        <button type="submit" class="btn-primary">Pay now</button>
                    </form>
                </div>
            </div>
        @endif

        <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_22rem]">
            <div class="space-y-6">
                <section class="card p-6">
                    <h2 class="font-display text-2xl font-semibold">Your cakes</h2>
                    <ul class="mt-5 space-y-4">
                        @foreach ($order->items as $item)
                            <li class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold">{{ $item->quantity }} × {{ $item->product_name }}</p>
                                    <p class="mt-0.5 text-xs text-cocoa">{{ $item->variant_name ?: 'Standard size' }} · ₱{{ number_format((float) $item->unit_price, 0) }} each</p>
                                    @if (! empty($item->customizations))
                                        <ul class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-xs text-cocoa">
                                            @foreach ($item->customizations as $customization)
                                                <li>{{ $customization['group'] }}: <span class="font-medium text-ink">{{ $customization['label'] }}</span></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if ($item->special_instructions)
                                        <p class="mt-2 rounded-lg bg-parchment/60 px-3 py-2 text-xs leading-5 text-cocoa">“{{ $item->special_instructions }}”</p>
                                    @endif
                                </div>
                                <p class="shrink-0 font-semibold">₱{{ number_format((float) $item->line_total, 0) }}</p>
                            </li>
                        @endforeach
                    </ul>

                    <dl class="mt-6 space-y-2.5 border-t border-ink/8 pt-5 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-cocoa">Subtotal</dt><dd>₱{{ number_format((float) $order->subtotal, 0) }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-cocoa">Delivery</dt><dd>₱{{ number_format((float) $order->delivery_fee, 0) }}</dd></div>
                        <div class="flex justify-between gap-3 border-t border-ink/8 pt-3"><dt class="font-semibold">Total</dt><dd class="font-display text-2xl font-semibold">₱{{ number_format((float) $order->total, 0) }}</dd></div>
                    </dl>
                </section>

                <section class="card p-6">
                    <h2 class="font-display text-2xl font-semibold">Fulfillment</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="label">Method</p>
                            <p class="font-medium">{{ $order->fulfillment_type->label() }}</p>
                            @if ($order->delivery_address)
                                <p class="mt-1 text-sm leading-6 text-cocoa">{{ $order->delivery_address }}</p>
                            @else
                                <p class="mt-1 text-sm leading-6 text-cocoa">{{ $order->shop->address }}, {{ $order->shop->city }}</p>
                            @endif
                        </div>
                        <div>
                            <p class="label">Scheduled</p>
                            <p class="font-medium">{{ $order->scheduled_date?->format('l, F j, Y') }}</p>
                            <p class="mt-1 text-sm text-cocoa">{{ \Illuminate\Support\Carbon::parse($order->scheduled_time)->format('g:i A') }}</p>
                        </div>
                        <div>
                            <p class="label">Customer</p>
                            <p class="font-medium">{{ $order->customer_name }}</p>
                            <p class="mt-1 text-sm text-cocoa">{{ $order->customer_email }}</p>
                            <p class="text-sm text-cocoa">{{ $order->customer_phone }}</p>
                        </div>
                        <div>
                            <p class="label">Bakery</p>
                            <p class="font-medium">{{ $order->shop->name }}</p>
                            <p class="mt-1 text-sm text-cocoa">{{ $order->shop->phone }}</p>
                            <a href="{{ route('shops.show', $order->shop) }}" class="mt-1 inline-flex text-sm font-semibold text-rose hover:underline">Visit storefront</a>
                        </div>
                    </div>

                    @if ($order->notes)
                        <div class="mt-5 rounded-xl bg-parchment/60 px-4 py-3 text-sm leading-6 text-cocoa">
                            <span class="font-semibold text-ink">Notes:</span> {{ $order->notes }}
                        </div>
                    @endif
                </section>

                <section class="card p-6">
                    <h2 class="font-display text-2xl font-semibold">Payment</h2>
                    @if ($order->payment)
                        <div class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                            <div>
                                <p class="label">Status</p>
                                <span class="badge {{ $order->payment->status->badgeClasses() }}">{{ $order->payment->status->label() }}</span>
                            </div>
                            <div>
                                <p class="label">Reference</p>
                                <p class="font-mono text-xs text-cocoa">{{ $order->payment->reference_number ?: $order->payment->provider_checkout_id ?: 'Awaiting provider' }}</p>
                            </div>
                            <div>
                                <p class="label">Amount</p>
                                <p class="font-semibold">₱{{ number_format((float) $order->payment->amount, 0) }}</p>
                            </div>
                            <div>
                                <p class="label">{{ $order->payment->paid_at ? 'Paid at' : 'Created' }}</p>
                                <p>{{ ($order->payment->paid_at ?? $order->payment->created_at)?->format('M j, Y g:i A') }}</p>
                            </div>
                        </div>
                    @else
                        <p class="mt-3 text-sm text-cocoa">No payment record has been created yet.</p>
                    @endif
                </section>
            </div>

            <aside class="space-y-5">
                <div class="card p-6">
                    <h2 class="font-display text-2xl font-semibold">Keep this link</h2>
                    <p class="mt-2 text-sm leading-6 text-cocoa">Anyone with this private link and your order number can view the order. We have not emailed it automatically.</p>
                    <div class="mt-4 flex gap-2">
                        <input type="text" readonly value="{{ $order->publicUrl() }}" class="input text-xs" onclick="this.select()">
                    </div>
                    <a href="{{ route('orders.track.form') }}" class="mt-4 btn-secondary btn-sm w-full">Track another order</a>
                </div>

                @if ($canCancel)
                    <div class="card p-6">
                        <h2 class="font-display text-xl font-semibold">Need to cancel?</h2>
                        <p class="mt-2 text-sm leading-6 text-cocoa">You can cancel online before the bakery confirms. Paid orders require a refund first.</p>
                        <form method="POST" action="{{ route('orders.cancel', ['order' => $order, 'token' => $order->public_token]) }}" class="mt-4" onsubmit="return confirm('Cancel this order?');">
                            @csrf
                            <button type="submit" class="btn-danger w-full">Cancel order</button>
                        </form>
                    </div>
                @endif

                <div class="rounded-3xl bg-ink p-6 text-cream">
                    <p class="eyebrow text-cream/60">Questions?</p>
                    <p class="mt-3 font-display text-xl font-semibold leading-snug">Talk to the bakery directly.</p>
                    <p class="mt-2 text-sm text-cream/70">{{ $order->shop->email }}</p>
                    <a href="mailto:{{ $order->shop->email }}" class="mt-4 inline-flex text-sm font-semibold text-cream underline decoration-cream/30 underline-offset-4 hover:decoration-cream">Send an email</a>
                </div>
            </aside>
        </div>
    </div>
@endsection
