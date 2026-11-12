@extends('layouts.admin')

@section('title', 'Orders')
@section('eyebrow', 'Marketplace')
@section('heading', 'Orders')

@section('actions')
    <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-wrap items-end gap-2">
        <div class="min-w-44 flex-1 sm:max-w-xs">
            <label for="q" class="sr-only">Search orders</label>
            <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}" class="input py-2 text-sm" placeholder="Order number or customer">
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
        <button type="submit" class="btn-secondary btn-sm">Filter</button>
    </form>
@endsection

@section('content')
    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Bakery</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-semibold hover:text-rose">{{ $order->order_number }}</a>
                                <p class="text-xs text-cocoa">{{ $order->placed_at?->format('M j, Y g:i A') }} · {{ $order->items_count }} items</p>
                            </td>
                            <td class="text-sm">{{ $order->shop?->name ?? '—' }}</td>
                            <td>
                                <p class="text-sm">{{ $order->customer_name }}</p>
                                <p class="text-xs text-cocoa">{{ $order->customer_email }}</p>
                            </td>
                            <td><span class="badge {{ $order->status->badgeClasses() }}">{{ $order->status->label() }}</span></td>
                            <td><span class="badge {{ $order->payment_status->badgeClasses() }}">{{ $order->payment_status->label() }}</span></td>
                            <td class="text-right font-semibold">₱{{ number_format((float) $order->total, 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-14 text-center text-sm text-cocoa">No orders match this view.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $orders->links() }}</div>
@endsection
