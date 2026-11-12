@extends('layouts.admin')

@section('title', 'Overview')
@section('eyebrow', 'Marketplace administration')
@section('heading', 'Overview')

@section('actions')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-cocoa">Last 30 days · {{ now()->format('M j, Y') }}</p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.applications.index') }}" class="btn-secondary btn-sm">Review applications</a>
            <a href="{{ route('admin.orders.index') }}" class="btn-primary btn-sm">All orders</a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="metric">
            <p class="metric-label">Marketplace GMV</p>
            <p class="metric-value mt-3">₱{{ number_format($metrics['revenue'], 0) }}</p>
            <p class="mt-1 text-xs {{ ($metrics['revenue_change'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose' }}">
                @if ($metrics['revenue_change'] === null)
                    No prior period
                @else
                    {{ $metrics['revenue_change'] >= 0 ? '+' : '' }}{{ $metrics['revenue_change'] }}% vs previous 30 days
                @endif
            </p>
        </div>
        <div class="metric">
            <p class="metric-label">Orders today</p>
            <p class="metric-value mt-3">{{ $metrics['orders_today'] }}</p>
            <a href="{{ route('admin.orders.index') }}" class="mt-1 inline-flex text-xs font-semibold text-rose hover:underline">View orders</a>
        </div>
        <div class="metric">
            <p class="metric-label">Active bakeries</p>
            <p class="metric-value mt-3">{{ $metrics['active_shops'] }}</p>
            <p class="mt-1 text-xs text-cocoa">{{ $metrics['pending_shops'] }} waiting for review</p>
        </div>
        <div class="metric">
            <p class="metric-label">Customers</p>
            <p class="metric-value mt-3">{{ number_format($metrics['customers']) }}</p>
            <p class="mt-1 text-xs text-cocoa">{{ $metrics['pending_payments'] }} pending payments</p>
        </div>
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-[1.55fr_1fr]">
        <section class="card p-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-semibold">Marketplace revenue</h2>
                    <p class="mt-1 text-xs text-cocoa">Paid orders across every bakery, last 14 days.</p>
                </div>
            </div>
            <div class="mt-5 h-64"><canvas id="adminRevenueChart"></canvas></div>
        </section>

        <section class="card p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-display text-xl font-semibold">Order health</h2>
                <a href="{{ route('admin.orders.index') }}" class="text-xs font-semibold text-rose hover:underline">All orders</a>
            </div>
            <ul class="mt-5 space-y-3">
                @foreach (\App\Enums\OrderStatus::cases() as $status)
                    <li class="flex items-center justify-between gap-3 text-sm">
                        <span class="text-cocoa">{{ $status->label() }}</span>
                        <span class="font-semibold">{{ (int) ($statusCounts[$status->value] ?? 0) }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-2">
        <section class="card overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-ink/8 px-5 py-4 sm:px-6">
                <h2 class="font-display text-xl font-semibold">Applications waiting</h2>
                <a href="{{ route('admin.applications.index') }}" class="text-xs font-semibold text-rose hover:underline">Open queue</a>
            </div>
            <ul class="divide-y divide-ink/8">
                @forelse ($pendingShops as $shop)
                    <li class="flex items-center justify-between gap-4 px-5 py-4 sm:px-6">
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $shop->name }}</p>
                            <p class="truncate text-xs text-cocoa">{{ $shop->city }}, {{ $shop->province }} · {{ $shop->owner?->email }}</p>
                        </div>
                        <a href="{{ route('admin.shops.show', $shop) }}" class="btn-secondary btn-sm">Review</a>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-cocoa">No applications waiting. Nice work.</li>
                @endforelse
            </ul>
        </section>

        <section class="card overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-ink/8 px-5 py-4 sm:px-6">
                <h2 class="font-display text-xl font-semibold">Top bakeries</h2>
                <a href="{{ route('admin.shops.index') }}" class="text-xs font-semibold text-rose hover:underline">All bakeries</a>
            </div>
            <ul class="divide-y divide-ink/8">
                @forelse ($topShops as $shop)
                    <li class="flex items-center justify-between gap-4 px-5 py-4 sm:px-6">
                        <div class="flex min-w-0 items-center gap-3">
                            <img src="{{ $shop->logoUrl() }}" alt="" class="h-9 w-9 rounded-full object-cover">
                            <div class="min-w-0">
                                <p class="truncate font-semibold">{{ $shop->name }}</p>
                                <p class="text-xs text-cocoa">{{ $shop->orders_count }} {{ \Illuminate\Support\Str::plural('order', $shop->orders_count) }}</p>
                            </div>
                        </div>
                        <p class="shrink-0 font-semibold">₱{{ number_format((float) ($shop->paid_revenue ?? 0), 0) }}</p>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-cocoa">No bakery revenue yet.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <section class="mt-5 card overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-ink/8 px-5 py-4 sm:px-6">
            <h2 class="font-display text-xl font-semibold">Recent marketplace orders</h2>
            <a href="{{ route('admin.orders.index') }}" class="text-xs font-semibold text-rose hover:underline">All orders</a>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Bakery</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold hover:text-rose">{{ $order->order_number }}</a><p class="text-xs text-cocoa">{{ $order->placed_at?->format('M j, Y g:i A') }}</p></td>
                            <td class="text-sm">{{ $order->shop?->name ?? '—' }}</td>
                            <td class="text-sm">{{ $order->customer_name }}</td>
                            <td><span class="badge {{ $order->status->badgeClasses() }}">{{ $order->status->label() }}</span></td>
                            <td class="text-right font-semibold">₱{{ number_format((float) $order->total, 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-12 text-center text-sm text-cocoa">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canvas = document.getElementById('adminRevenueChart');
            if (!canvas || !window.Chart) return;

            const daily = @json($daily);
            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: daily.map((day) => day.label),
                    datasets: [{
                        label: 'Revenue',
                        data: daily.map((day) => day.revenue),
                        borderColor: '#2b211b',
                        backgroundColor: 'rgba(198, 161, 91, 0.18)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 0,
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: '#6b5748', font: { size: 10 } } },
                        y: { beginAtZero: true, grid: { color: 'rgba(43,33,27,0.06)' }, ticks: { color: '#6b5748', font: { size: 10 }, callback: (value) => '₱' + value.toLocaleString() } }
                    }
                }
            });
        });
    </script>
@endpush
