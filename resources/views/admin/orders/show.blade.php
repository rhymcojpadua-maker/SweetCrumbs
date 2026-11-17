@extends('layouts.admin')

@section('title', $order->order_number)
@section('eyebrow', 'Order detail')
@section('heading', $order->order_number)

@section('actions')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="badge {{ $order->status->badgeClasses() }}">{{ $order->status->label() }}</span>
            <span class="badge {{ $order->payment_status->badgeClasses() }}">{{ $order->payment_status->label() }}</span>
            <span class="text-sm text-cocoa">{{ $order->payment_method === 'cash_on_delivery' ? 'Cash on delivery' : 'Online payment' }}</span>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="btn-secondary btn-sm">All orders</a>
    </div>
@endsection

@section('content')
    <div class="grid gap-5 xl:grid-cols-[1fr_22rem]">
        <div class="space-y-5">
            <section class="card p-5 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="font-display text-2xl font-semibold">Items</h2>
                        <p class="mt-1 text-sm text-cocoa">Fulfilled by {{ $order->shop?->name ?? 'unknown bakery' }}</p>
                    </div>
                    <p class="font-display text-3xl font-semibold">₱{{ number_format((float) $order->total, 0) }}</p>
                </div>
                <ul class="mt-5 divide-y divide-ink/8">
                    @foreach ($order->items as $item)
                        <li class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0">
                            <div>
                                <p class="font-medium">{{ $item->quantity }} × {{ $item->product_name }}</p>
                                <p class="text-xs text-cocoa">{{ $item->variant_name ?: 'Standard size' }} · ₱{{ number_format((float) $item->unit_price, 0) }} each</p>
                                @if (! empty($item->customizations))
                                    <p class="mt-1 text-xs text-cocoa">{{ collect($item->customizations)->map(fn ($c) => $c['group'].': '.$c['label'])->join(' · ') }}</p>
                                @endif
                            </div>
                            <p class="shrink-0 font-semibold">₱{{ number_format((float) $item->line_total, 0) }}</p>
                        </li>
                    @endforeach
                </ul>
                <dl class="mt-5 space-y-2 border-t border-ink/8 pt-4 text-sm">
                    <div class="flex justify-between"><dt class="text-cocoa">Subtotal</dt><dd>₱{{ number_format((float) $order->subtotal, 0) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-cocoa">Delivery</dt><dd>₱{{ number_format((float) $order->delivery_fee, 0) }}</dd></div>
                </dl>
            </section>

            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-2xl font-semibold">Payment activity</h2>
                @if ($order->payment)
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <span class="badge {{ $order->payment->status->badgeClasses() }}">{{ $order->payment->status->label() }}</span>
                        <span class="font-mono text-xs text-cocoa">{{ $order->payment->reference_number ?: $order->payment->provider_checkout_id ?: 'No reference' }}</span>
                    </div>
                    @if ($order->payment->transactions->isNotEmpty())
                        <div class="mt-4 overflow-hidden rounded-xl border border-ink/8">
                            <table class="table">
                                <thead><tr><th>When</th><th>Event</th><th>Reference</th><th class="text-right">Amount</th></tr></thead>
                                <tbody>
                                    @foreach ($order->payment->transactions as $transaction)
                                        <tr>
                                            <td class="text-xs text-cocoa">{{ $transaction->created_at->format('M j, g:i A') }}</td>
                                            <td class="font-medium">{{ $transaction->event_type }}</td>
                                            <td class="font-mono text-xs text-cocoa">{{ $transaction->provider_transaction_id ?: '—' }}</td>
                                            <td class="text-right">₱{{ number_format((float) $transaction->amount, 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="mt-3 text-sm text-cocoa">No payment transactions recorded.</p>
                    @endif
                @else
                    <p class="mt-3 text-sm text-cocoa">No payment record for this order.</p>
                @endif
            </section>
        </div>

        <aside class="space-y-5">
            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-xl font-semibold">Order</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="label">Bakery</dt><dd>@if ($order->shop)<a href="{{ route('admin.shops.show', $order->shop) }}" class="font-semibold text-rose hover:underline">{{ $order->shop->name }}</a>@endif</dd></div>
                    <div><dt class="label">Placed</dt><dd>{{ $order->placed_at?->format('M j, Y g:i A') }}</dd></div>
                    <div><dt class="label">Method</dt><dd>{{ $order->fulfillment_type->label() }}</dd></div>
                    <div><dt class="label">Schedule</dt><dd>{{ $order->scheduled_date?->format('M j, Y') }} · {{ \Illuminate\Support\Carbon::parse($order->scheduled_time)->format('g:i A') }}</dd></div>
                    <div><dt class="label">Delivery address</dt><dd>{{ $order->delivery_address ?: 'Pickup' }}</dd></div>
                </dl>
            </section>

            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-xl font-semibold">Customer</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div><dt class="label">Name</dt><dd class="font-medium">{{ $order->customer_name }}</dd></div>
                    <div><dt class="label">Email</dt><dd class="break-all">{{ $order->customer_email }}</dd></div>
                    <div><dt class="label">Phone</dt><dd>{{ $order->customer_phone }}</dd></div>
                </dl>
                @if ($order->notes)
                    <p class="mt-4 rounded-lg bg-parchment/60 px-3 py-2 text-xs leading-5 text-cocoa">{{ $order->notes }}</p>
                @endif
            </section>

            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-xl font-semibold">Customer link</h2>
                <a href="{{ $order->publicUrl() }}" target="_blank" rel="noopener" class="mt-3 btn-secondary btn-sm w-full">Open customer view</a>
            </section>
        </aside>
    </div>
@endsection
