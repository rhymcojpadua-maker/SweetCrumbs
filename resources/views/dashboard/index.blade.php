@extends('layouts.dashboard')

@section('title', 'Overview')
@section('eyebrow', 'Bakery dashboard')
@section('heading', $shop->name)

@section('actions')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-cocoa">Last 30 days · {{ now()->format('M j, Y') }}</p>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('dashboard.orders.index') }}" class="btn-secondary btn-sm">View orders</a>
            <a href="{{ route('dashboard.products.create') }}" class="btn-primary btn-sm">
                @include('partials.icon', ['name' => 'plus', 'class' => 'h-4 w-4'])
                Add a cake
            </a>
        </div>
    </div>
@endsection

@section('content')
    @if ($shop->status !== \App\Enums\ShopStatus::Active)
        <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <span class="font-semibold">Your bakery is {{ $shop->status->label() }}.</span>
            <a href="{{ route('dashboard.pending') }}" class="font-semibold underline underline-offset-4">See application status</a>
        </div>
    @endif

    <div class="grid gap-4 xl:grid-cols-4">
        <div class="metric">
            <div class="flex items-center justify-between gap-2">
                <p class="metric-label">Revenue</p>
                @include('partials.icon', ['name' => 'money', 'class' => 'h-5 w-5 text-rose'])
            </div>
            <p class="metric-value mt-3">₱{{ number_format($revenue, 0) }}</p>
            <p class="mt-1 text-xs {{ ($revenueChange ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose' }}">
                @if ($revenueChange === null)
                    No prior period
                @else
                    {{ $revenueChange >= 0 ? '+' : '' }}{{ $revenueChange }}% vs previous 30 days
                @endif
            </p>
        </div>
        <div class="metric">
            <div class="flex items-center justify-between gap-2">
                <p class="metric-label">Orders</p>
                @include('partials.icon', ['name' => 'orders', 'class' => 'h-5 w-5 text-cocoa'])
            </div>
            <p class="metric-value mt-3">{{ number_format($orderCount) }}</p>
            <p class="mt-1 text-xs {{ ($orderChange ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose' }}">
                @if ($orderChange === null)
                    No prior period
                @else
                    {{ $orderChange >= 0 ? '+' : '' }}{{ $orderChange }}% vs previous 30 days
                @endif
            </p>
        </div>
        <div class="metric">
            <div class="flex items-center justify-between gap-2">
                <p class="metric-label">Needs confirmation</p>
                @include('partials.icon', ['name' => 'clock', 'class' => 'h-5 w-5 text-gold'])
            </div>
            <p class="metric-value mt-3">{{ $pendingConfirmation }}</p>
            <a href="{{ route('dashboard.orders.index', ['status' => \App\Enums\OrderStatus::PendingConfirmation->value]) }}" class="mt-1 inline-flex text-xs font-semibold text-rose hover:underline">Review requests</a>
        </div>
        <div class="metric">
            <div class="flex items-center justify-between gap-2">
                <p class="metric-label">Today's orders</p>
                @include('partials.icon', ['name' => 'calendar', 'class' => 'h-5 w-5 text-sage'])
            </div>
            <p class="metric-value mt-3">{{ $todayOrders }}</p>
            <p class="mt-1 text-xs text-cocoa">{{ $shop->orders_count }} orders all time</p>
        </div>
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-[1.55fr_1fr]">
        <section class="card p-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-semibold">Revenue rhythm</h2>
                    <p class="mt-1 text-xs text-cocoa">Paid orders over the last 14 days.</p>
                </div>
                <a href="{{ route('dashboard.reports.index') }}" class="btn-ghost btn-sm">Full reports</a>
            </div>
            <div class="mt-5 h-64">
                <canvas id="revenueChart"></canvas>
            </div>
        </section>

        <section class="card p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-semibold">Order mix</h2>
                    <p class="mt-1 text-xs text-cocoa">Every order, by current status.</p>
                </div>
            </div>
            <ul class="mt-5 space-y-3">
                @foreach (\App\Enums\OrderStatus::cases() as $status)
                    @php($count = (int) ($statusCounts[$status->value] ?? 0))
                    <li class="flex items-center justify-between gap-3 text-sm">
                        <span class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-cocoa/30"></span>
                            <span class="text-cocoa">{{ $status->label() }}</span>
                        </span>
                        <span class="font-semibold">{{ $count }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-3">
        <section class="card overflow-hidden xl:col-span-2">
            <div class="flex items-center justify-between gap-3 border-b border-ink/8 px-5 py-4 sm:px-6">
                <h2 class="font-display text-xl font-semibold">Recent orders</h2>
                <a href="{{ route('dashboard.orders.index') }}" class="text-xs font-semibold text-rose hover:underline">All orders</a>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('dashboard.orders.show', $order) }}" class="font-semibold hover:text-rose">{{ $order->order_number }}</a>
                                    <p class="mt-0.5 text-xs text-cocoa">{{ $order->items_count }} {{ \Illuminate\Support\Str::plural('item', $order->items_count) }} · {{ $order->placed_at?->format('M j') }}</p>
                                </td>
                                <td>
                                    <p class="font-medium">{{ $order->customer_name }}</p>
                                    <p class="text-xs text-cocoa">{{ $order->customer_phone }}</p>
                                </td>
                                <td>
                                    <span class="badge {{ $order->status->badgeClasses() }}">{{ $order->status->label() }}</span>
                                    @if (! $order->isPaid())
                                        <p class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-amber-700">Unpaid</p>
                                    @endif
                                </td>
                                <td class="text-right font-semibold">₱{{ number_format((float) $order->total, 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-10 text-center text-sm text-cocoa">No orders yet. Your first one will appear here.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-display text-xl font-semibold">Low stock</h2>
                <a href="{{ route('dashboard.inventory.index') }}" class="text-xs font-semibold text-rose hover:underline">Manage</a>
            </div>
            <ul class="mt-4 space-y-3">
                @forelse ($lowStock as $product)
                    <li class="flex items-center gap-3">
                        <img src="{{ $product->imageUrl() }}" alt="" class="h-10 w-10 rounded-xl object-cover">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $product->name }}</p>
                            <p class="text-xs text-cocoa">Threshold {{ $product->low_stock_threshold }}</p>
                        </div>
                        <span class="badge {{ $product->stock_quantity === 0 ? 'bg-rose/10 text-rose ring-rose/20' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">{{ $product->stock_quantity }} left</span>
                    </li>
                @empty
                    <li class="rounded-xl bg-sage/10 px-4 py-5 text-center text-sm text-sage">Everything is comfortably stocked.</li>
                @endforelse
            </ul>

            <div class="mt-6 border-t border-ink/8 pt-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-display text-xl font-semibold">Best sellers</h2>
                </div>
                <ul class="mt-4 space-y-3">
                    @forelse ($topProducts as $product)
                        <li class="flex items-center justify-between gap-3 text-sm">
                            <div class="min-w-0">
                                <p class="truncate font-medium">{{ $product->product_name }}</p>
                                <p class="text-xs text-cocoa">{{ (int) $product->units }} sold</p>
                            </div>
                            <span class="font-semibold">₱{{ number_format((float) $product->revenue, 0) }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-cocoa">No paid orders in this period yet.</li>
                    @endforelse
                </ul>
            </div>
        </section>
    </div>

    <section class="mt-5 card overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-ink/8 px-5 py-4 sm:px-6">
            <h2 class="font-display text-xl font-semibold">Coming up on the calendar</h2>
            <a href="{{ route('dashboard.fulfillment.edit') }}" class="text-xs font-semibold text-rose hover:underline">Fulfillment settings</a>
        </div>
        <div class="grid gap-px bg-ink/8 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($upcoming as $order)
                <a href="{{ route('dashboard.orders.show', $order) }}" class="bg-white p-5 transition hover:bg-parchment/40">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-cocoa/70">{{ $order->scheduled_date?->format('D, M j') }} · {{ \Illuminate\Support\Carbon::parse($order->scheduled_time)->format('g:i A') }}</p>
                            <p class="mt-2 font-semibold">{{ $order->customer_name }}</p>
                            <p class="text-sm text-cocoa">{{ $order->items->count() }} {{ \Illuminate\Support\Str::plural('cake', $order->items->count()) }} · {{ $order->fulfillment_type->label() }}</p>
                        </div>
                        <span class="badge {{ $order->status->badgeClasses() }}">{{ $order->status->label() }}</span>
                    </div>
                </a>
            @empty
                <p class="col-span-full bg-white px-5 py-10 text-center text-sm text-cocoa">No upcoming orders on the calendar.</p>
            @endforelse
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canvas = document.getElementById('revenueChart');
            if (!canvas || !window.Chart) return;

            const daily = @json($daily);
            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: daily.map((day) => day.label),
                    datasets: [{
                        label: 'Revenue',
                        data: daily.map((day) => day.revenue),
                        borderColor: '#b96b7c',
                        backgroundColor: 'rgba(185, 107, 124, 0.12)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 0,
                        pointHoverRadius: 4,
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
