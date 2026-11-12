@extends('layouts.dashboard')

@section('title', $customer->name)
@section('eyebrow', 'Customer history')
@section('heading', $customer->name)

@section('actions')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('dashboard.customers.index') }}" class="text-sm font-semibold text-cocoa hover:text-ink">← All customers</a>
        <a href="mailto:{{ $customer->email }}" class="btn-primary btn-sm">Email customer</a>
    </div>
@endsection

@section('content')
    <div class="grid gap-5 xl:grid-cols-[1fr_20rem]">
        <section class="card overflow-hidden">
            <div class="border-b border-ink/8 px-5 py-4 sm:px-6">
                <h2 class="font-display text-xl font-semibold">Orders with your bakery</h2>
                <p class="mt-1 text-xs text-cocoa">Matched by email address at checkout.</p>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('dashboard.orders.show', $order) }}" class="font-semibold hover:text-rose">{{ $order->order_number }}</a>
                                    <p class="text-xs text-cocoa">{{ $order->items_count }} {{ \Illuminate\Support\Str::plural('item', $order->items_count) }}</p>
                                </td>
                                <td class="text-sm text-cocoa">{{ $order->placed_at?->format('M j, Y') }}</td>
                                <td><span class="badge {{ $order->status->badgeClasses() }}">{{ $order->status->label() }}</span></td>
                                <td class="text-right font-semibold">₱{{ number_format((float) $order->total, 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-12 text-center text-sm text-cocoa">No orders from this customer yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-ink/8 px-5 py-4 sm:px-6">{{ $orders->links() }}</div>
        </section>

        <aside class="space-y-5">
            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-xl font-semibold">At a glance</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="label">Name</dt><dd class="font-medium">{{ $customer->name }}</dd></div>
                    <div><dt class="label">Email</dt><dd class="font-medium break-all">{{ $customer->email }}</dd></div>
                    <div><dt class="label">Phone</dt><dd class="font-medium">{{ $customer->phone ?: '—' }}</dd></div>
                    <div><dt class="label">Orders</dt><dd class="font-medium">{{ $customer->order_count }}</dd></div>
                    <div><dt class="label">Lifetime spend</dt><dd class="font-display text-2xl font-semibold">₱{{ number_format((float) $customer->total_spent, 0) }}</dd></div>
                    <div><dt class="label">Last order</dt><dd class="font-medium">{{ $customer->last_order_at?->format('M j, Y') ?? '—' }}</dd></div>
                </dl>
            </section>
            <section class="card-flat p-5 text-sm leading-6 text-cocoa">
                <p class="font-semibold text-ink">Privacy reminder</p>
                <p class="mt-1">Customer contact details are only for fulfilling and supporting orders from your bakery.</p>
            </section>
        </aside>
    </div>
@endsection
