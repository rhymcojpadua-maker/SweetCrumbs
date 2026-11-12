@extends('layouts.admin')

@section('title', 'Payments')
@section('eyebrow', 'Finance')
@section('heading', 'Payments')

@section('actions')
    <form method="GET" action="{{ route('admin.payments.index') }}" class="flex flex-wrap items-end gap-2">
        <div class="min-w-44 flex-1 sm:max-w-xs">
            <label for="q" class="sr-only">Search payments</label>
            <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}" class="input py-2 text-sm" placeholder="Reference or bakery">
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
        <button type="submit" class="btn-secondary btn-sm">Filter</button>
    </form>
@endsection

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([\App\Enums\PaymentStatus::Paid, \App\Enums\PaymentStatus::Pending, \App\Enums\PaymentStatus::Failed, \App\Enums\PaymentStatus::Refunded] as $status)
            @php($row = $summary[$status->value] ?? null)
            <div class="metric">
                <p class="metric-label">{{ $status->label() }}</p>
                <p class="metric-value mt-3">₱{{ number_format((float) ($row?->amount ?? 0), 0) }}</p>
                <p class="mt-1 text-xs text-cocoa">{{ (int) ($row?->total ?? 0) }} {{ \Illuminate\Support\Str::plural('payment', (int) ($row?->total ?? 0)) }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-5 card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Bakery</th>
                        <th>Reference</th>
                        <th>Status</th>
                        <th>Paid at</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>
                                @if ($payment->order)
                                    <a href="{{ route('admin.orders.show', $payment->order) }}" class="font-semibold hover:text-rose">{{ $payment->order->order_number }}</a>
                                    <p class="text-xs text-cocoa">{{ $payment->order->customer_name }}</p>
                                @else
                                    <span class="text-cocoa">Order removed</span>
                                @endif
                            </td>
                            <td class="text-sm">{{ $payment->shop?->name ?? '—' }}</td>
                            <td class="font-mono text-xs text-cocoa">{{ $payment->reference_number ?: $payment->provider_checkout_id ?: '—' }}</td>
                            <td><span class="badge {{ $payment->status->badgeClasses() }}">{{ $payment->status->label() }}</span></td>
                            <td class="text-sm text-cocoa">{{ $payment->paid_at?->format('M j, Y g:i A') ?? '—' }}</td>
                            <td class="text-right font-semibold">₱{{ number_format((float) $payment->amount, 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-14 text-center text-sm text-cocoa">No payments match this view.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $payments->links() }}</div>
@endsection
