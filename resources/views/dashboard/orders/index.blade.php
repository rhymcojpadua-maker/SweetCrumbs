@extends('layouts.dashboard')

@section('title', 'Orders')
@section('eyebrow', 'Operations')
@section('heading', 'Orders')

@section('actions')
    <form method="GET" action="{{ route('dashboard.orders.index') }}" class="flex flex-wrap items-end gap-2">
        <div class="min-w-44 flex-1 sm:max-w-xs">
            <label for="q" class="sr-only">Search orders</label>
            <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}" class="input py-2 text-sm" placeholder="Order number, customer, phone">
        </div>
        <div>
            <label for="status" class="sr-only">Status</label>
            <select name="status" id="status" class="input py-2 text-sm">
                <option value="">Any status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="payment_status" class="sr-only">Payment</label>
            <select name="payment_status" id="payment_status" class="input py-2 text-sm">
                <option value="">Any payment</option>
                @foreach ($paymentStatuses as $status)
                    <option value="{{ $status->value }}" @selected(($filters['payment_status'] ?? null) === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="date" class="sr-only">Scheduled date</label>
            <input type="date" name="date" id="date" value="{{ $filters['date'] ?? '' }}" class="input py-2 text-sm">
        </div>
        <button type="submit" class="btn-secondary btn-sm">Filter</button>
        @if (array_filter($filters))
            <a href="{{ route('dashboard.orders.index') }}" class="btn-ghost btn-sm">Clear</a>
        @endif
    </form>
@endsection

@section('content')
    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th class="text-right">Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('dashboard.orders.show', $order) }}" class="font-semibold hover:text-rose">{{ $order->order_number }}</a>
                                <p class="mt-0.5 text-xs text-cocoa">{{ $order->items_count }} {{ \Illuminate\Support\Str::plural('item', $order->items_count) }} · {{ $order->fulfillment_type->label() }}</p>
                            </td>
                            <td>
                                <p class="font-medium">{{ $order->customer_name }}</p>
                                <p class="text-xs text-cocoa">{{ $order->customer_phone }}</p>
                            </td>
                            <td>
                                <p class="text-sm font-medium">{{ $order->scheduled_date?->format('M j, Y') }}</p>
                                <p class="text-xs text-cocoa">{{ \Illuminate\Support\Carbon::parse($order->scheduled_time)->format('g:i A') }}</p>
                            </td>
                            <td><span class="badge {{ $order->status->badgeClasses() }}">{{ $order->status->label() }}</span></td>
                            <td><span class="badge {{ $order->payment_status->badgeClasses() }}">{{ $order->payment_status->label() }}</span></td>
                            <td class="text-right font-semibold">₱{{ number_format((float) $order->total, 0) }}</td>
                            <td class="text-right">
                                <a href="{{ route('dashboard.orders.show', $order) }}" class="btn-ghost btn-sm">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-14 text-center">
                                <p class="font-display text-2xl font-semibold">No orders match this view.</p>
                                <p class="mt-1 text-sm text-cocoa">Try clearing the filters, or share your storefront to bring the first order in.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $orders->links() }}</div>
@endsection
