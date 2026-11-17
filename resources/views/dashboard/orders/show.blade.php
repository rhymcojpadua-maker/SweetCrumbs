@extends('layouts.dashboard')

@section('title', $order->order_number)
@section('eyebrow', 'Order detail')
@section('heading', $order->order_number)

@section('actions')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="badge {{ $order->status->badgeClasses() }}">{{ $order->status->label() }}</span>
            <span class="badge {{ $order->payment_status->badgeClasses() }}">{{ $order->payment_status->label() }}</span>
            <span class="text-xs text-cocoa">Placed {{ $order->placed_at?->format('M j, Y g:i A') }}</span>
        </div>
        <a href="{{ route('dashboard.orders.index') }}" class="btn-secondary btn-sm">All orders</a>
    </div>
@endsection

@section('content')
    <div class="grid gap-5 xl:grid-cols-[1fr_22rem]">
        <div class="space-y-5">
            <section class="card p-5 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="font-display text-2xl font-semibold">The cakes</h2>
                        <p class="mt-1 text-sm text-cocoa">{{ $order->items->count() }} {{ \Illuminate\Support\Str::plural('line item', $order->items->count()) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs uppercase tracking-wide text-cocoa">Order total</p>
                        <p class="font-display text-3xl font-semibold">₱{{ number_format((float) $order->total, 0) }}</p>
                    </div>
                </div>

                <ul class="mt-6 divide-y divide-ink/8">
                    @foreach ($order->items as $item)
                        <li class="flex items-start justify-between gap-4 py-4 first:pt-0 last:pb-0">
                            <div>
                                <p class="font-semibold">{{ $item->quantity }} × {{ $item->product_name }}</p>
                                <p class="mt-0.5 text-xs text-cocoa">{{ $item->variant_name ?: 'Standard size' }} · ₱{{ number_format((float) $item->unit_price, 0) }} each</p>
                                @if (! empty($item->customizations))
                                    <ul class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-cocoa">
                                        @foreach ($item->customizations as $customization)
                                            <li>{{ $customization['group'] }}: <span class="font-medium text-ink">{{ $customization['label'] }}</span>@if (($customization['price'] ?? 0) > 0) (+₱{{ number_format((float) $customization['price'], 0) }})@endif</li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if ($item->special_instructions)
                                    <p class="mt-2 rounded-lg bg-parchment/70 px-3 py-2 text-xs leading-5 text-cocoa">“{{ $item->special_instructions }}”</p>
                                @endif
                            </div>
                            <p class="shrink-0 font-semibold">₱{{ number_format((float) $item->line_total, 0) }}</p>
                        </li>
                    @endforeach
                </ul>

                <dl class="mt-6 space-y-2.5 border-t border-ink/8 pt-5 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-cocoa">Subtotal</dt><dd>₱{{ number_format((float) $order->subtotal, 0) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-cocoa">Delivery</dt><dd>₱{{ number_format((float) $order->delivery_fee, 0) }}</dd></div>
                    <div class="flex justify-between gap-3 border-t border-ink/8 pt-3 text-base"><dt class="font-semibold">Total</dt><dd class="font-display text-xl font-semibold">₱{{ number_format((float) $order->total, 0) }}</dd></div>
                </dl>
            </section>

            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-2xl font-semibold">Customer &amp; fulfillment</h2>
                <div class="mt-5 grid gap-6 sm:grid-cols-2">
                    <div>
                        <p class="label">Customer</p>
                        <p class="font-semibold">{{ $order->customer_name }}</p>
                        <p class="mt-1 text-sm text-cocoa">{{ $order->customer_email }}</p>
                        <p class="text-sm text-cocoa">{{ $order->customer_phone }}</p>
                        @if ($order->customer)
                            <a href="{{ route('dashboard.customers.show', $order->customer) }}" class="mt-2 inline-flex text-xs font-semibold text-rose hover:underline">Customer history</a>
                        @endif
                    </div>
                    <div>
                        <p class="label">Method</p>
                        <p class="font-semibold">{{ $order->fulfillment_type->label() }}</p>
                        @if ($order->delivery_address)
                            <p class="mt-1 text-sm leading-6 text-cocoa">{{ $order->delivery_address }}</p>
                        @else
                            <p class="mt-1 text-sm leading-6 text-cocoa">Pickup at {{ $order->shop->address }}, {{ $order->shop->city }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="label">Scheduled</p>
                        <p class="font-semibold">{{ $order->scheduled_date?->format('l, F j, Y') }}</p>
                        <p class="mt-1 text-sm text-cocoa">{{ \Illuminate\Support\Carbon::parse($order->scheduled_time)->format('g:i A') }}</p>
                    </div>
                    <div>
                        <p class="label">Placed</p>
                        <p class="font-semibold">{{ $order->placed_at?->format('M j, Y') }}</p>
                        <p class="mt-1 text-sm text-cocoa">{{ $order->created_at->format('g:i A') }}</p>
                    </div>
                </div>

                @if ($order->notes)
                    <div class="mt-5 rounded-xl bg-parchment/60 px-4 py-3 text-sm leading-6 text-cocoa">
                        <span class="font-semibold text-ink">Customer notes:</span> {{ $order->notes }}
                    </div>
                @endif
            </section>

            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-2xl font-semibold">Payment record</h2>
                @if ($order->payment)
                    <div class="mt-5 grid gap-4 text-sm sm:grid-cols-3">
                        <div>
                            <p class="label">Status</p>
                            <span class="badge {{ $order->payment->status->badgeClasses() }}">{{ $order->payment->status->label() }}</span>
                        </div>
                        <div>
                            <p class="label">Method</p>
                            <p class="font-medium">{{ $order->payment_method === 'cash_on_delivery' ? 'Cash on delivery' : 'Online payment' }}</p>
                        </div>
                        <div>
                            <p class="label">Amount</p>
                            <p class="font-semibold">₱{{ number_format((float) $order->payment->amount, 0) }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="label">References</p>
                            <p class="font-mono text-xs leading-5 text-cocoa">Checkout: {{ $order->payment->provider_checkout_id ?: '—' }}</p>
                            <p class="font-mono text-xs leading-5 text-cocoa">Payment: {{ $order->payment->provider_payment_id ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="label">{{ $order->payment->paid_at ? 'Paid at' : 'Created' }}</p>
                            <p class="font-medium">{{ ($order->payment->paid_at ?? $order->payment->created_at)?->format('M j, Y g:i A') }}</p>
                        </div>
                    </div>
                    @if ($order->payment_method === 'cash_on_delivery' && $order->payment->status === \App\Enums\PaymentStatus::Unpaid)
                        <p class="mt-4 rounded-xl bg-parchment/60 px-4 py-3 text-sm leading-6 text-cocoa">Collect ₱{{ number_format((float) $order->total, 0) }} in cash when delivering this order.</p>
                        @if (in_array($order->status, [\App\Enums\OrderStatus::OutForDelivery, \App\Enums\OrderStatus::Completed], true))
                            <form method="POST" action="{{ route('dashboard.orders.cash-payment', $order) }}" class="mt-4">
                                @csrf
                                <button type="submit" class="btn-primary w-full">Mark cash received</button>
                            </form>
                        @endif
                    @endif
                @else
                    <p class="mt-3 text-sm text-cocoa">No payment record yet.</p>
                @endif

                @if ($transactions->isNotEmpty())
                    <div class="mt-6 overflow-hidden rounded-xl border border-ink/8">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>When</th>
                                    <th>Event</th>
                                    <th>Reference</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transactions as $transaction)
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
                @endif
            </section>
        </div>

        <aside class="space-y-5">
            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-xl font-semibold">Update status</h2>
                @if ($allowedStatuses->isEmpty())
                    <p class="mt-2 text-sm text-cocoa">This order is in a final state. No further transitions are available.</p>
                @else
                    <form method="POST" action="{{ route('dashboard.orders.status', $order) }}" class="mt-4 space-y-3">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label for="status" class="label">Next step</label>
                            <select name="status" id="status" required class="input">
                                <option value="">Choose a status</option>
                                @foreach ($allowedStatuses as $status)
                                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="note" class="label">Internal note (optional)</label>
                            <textarea name="note" id="note" rows="2" maxlength="500" class="input" placeholder="Visible in the status history."></textarea>
                        </div>
                        <button type="submit" class="btn-primary w-full">Save status</button>
                        <p class="text-center text-xs leading-5 text-cocoa">The customer's order page reflects the new status immediately.</p>
                    </form>
                @endif
            </section>

            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-xl font-semibold">Order link</h2>
                <p class="mt-2 text-xs leading-5 text-cocoa">Share this private link with the customer if they cannot find it.</p>
                <input type="text" readonly value="{{ $order->publicUrl() }}" class="input mt-3 text-xs" onclick="this.select()">
                <a href="{{ $order->publicUrl() }}" target="_blank" rel="noopener" class="mt-3 btn-secondary btn-sm w-full">Open customer view</a>
            </section>

            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-xl font-semibold">Quick facts</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-cocoa">Items</dt><dd class="font-medium">{{ $order->items->sum('quantity') }} cakes</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-cocoa">Payment</dt><dd class="font-medium">{{ $order->payment_status->label() }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-cocoa">Method</dt><dd class="font-medium">{{ $order->fulfillment_type->label() }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-cocoa">Scheduled</dt><dd class="font-medium">{{ $order->scheduled_date?->format('M j') }}</dd></div>
                </dl>
            </section>
        </aside>
    </div>
@endsection
