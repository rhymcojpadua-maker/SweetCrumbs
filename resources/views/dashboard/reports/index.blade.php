@extends('layouts.dashboard')

@section('title', 'Reports')
@section('eyebrow', 'Insights')
@section('heading', 'Reports')

@section('actions')
    <form method="GET" action="{{ route('dashboard.reports.index') }}" class="flex flex-wrap items-end gap-2">
        <div>
            <label for="from" class="label text-[10px]">From</label>
            <input type="date" name="from" id="from" value="{{ $from->toDateString() }}" class="input py-2 text-sm">
        </div>
        <div>
            <label for="to" class="label text-[10px]">To</label>
            <input type="date" name="to" id="to" value="{{ $to->toDateString() }}" class="input py-2 text-sm">
        </div>
        <button type="submit" class="btn-secondary btn-sm">Apply</button>
        <a href="{{ route('dashboard.reports.index') }}" class="btn-ghost btn-sm">Last 30 days</a>
    </form>
@endsection

@section('content')
    <p class="text-sm text-cocoa">{{ $from->format('M j, Y') }} — {{ $to->format('M j, Y') }}</p>

    <div class="mt-3 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="metric">
            <p class="metric-label">Paid revenue</p>
            <p class="metric-value mt-3">₱{{ number_format($revenue, 0) }}</p>
        </div>
        <div class="metric">
            <p class="metric-label">Orders</p>
            <p class="metric-value mt-3">{{ $orderCount }}</p>
        </div>
        <div class="metric">
            <p class="metric-label">Paid orders</p>
            <p class="metric-value mt-3">{{ $paidCount }}</p>
        </div>
        <div class="metric">
            <p class="metric-label">Average order</p>
            <p class="metric-value mt-3">₱{{ number_format($averageOrder, 0) }}</p>
        </div>
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-[1.5fr_1fr]">
        <section class="card p-5 sm:p-6">
            <h2 class="font-display text-xl font-semibold">Revenue over time</h2>
            <p class="mt-1 text-xs text-cocoa">Paid orders only, excluding cancelled and rejected.</p>
            <div class="mt-5 h-72"><canvas id="reportChart"></canvas></div>
        </section>

        <section class="card p-5 sm:p-6">
            <h2 class="font-display text-xl font-semibold">Fulfillment mix</h2>
            <div class="mt-5 space-y-4">
                @forelse ($fulfillment as $type => $count)
                    @php($label = \App\Enums\FulfillmentType::tryFrom($type)?->label() ?? ucfirst($type))
                    <div>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="font-medium">{{ $label }}</span>
                            <span class="text-cocoa">{{ $count }} {{ \Illuminate\Support\Str::plural('order', $count) }}</span>
                        </div>
                        @php($percent = $orderCount > 0 ? round(($count / $orderCount) * 100) : 0)
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-parchment">
                            <div class="h-full rounded-full bg-rose" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-cocoa">No orders in this period.</p>
                @endforelse
            </div>

            <div class="mt-6 border-t border-ink/8 pt-5">
                <h3 class="font-display text-lg font-semibold">By status</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($statuses as $status => $count)
                        <li class="flex items-center justify-between gap-3">
                            <span class="text-cocoa">{{ \App\Enums\OrderStatus::tryFrom($status)?->label() ?? ucfirst($status) }}</span>
                            <span class="font-semibold">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-2">
        <section class="card overflow-hidden">
            <div class="border-b border-ink/8 px-5 py-4 sm:px-6">
                <h2 class="font-display text-xl font-semibold">Top cakes</h2>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Cake</th><th>Units</th><th class="text-right">Revenue</th></tr></thead>
                    <tbody>
                        @forelse ($topProducts as $product)
                            <tr>
                                <td class="font-medium">{{ $product->product_name }}</td>
                                <td>{{ (int) $product->units }}</td>
                                <td class="text-right font-semibold">₱{{ number_format((float) $product->revenue, 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-10 text-center text-sm text-cocoa">No paid products in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card overflow-hidden">
            <div class="border-b border-ink/8 px-5 py-4 sm:px-6">
                <h2 class="font-display text-xl font-semibold">Revenue by category</h2>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Category</th><th>Units</th><th class="text-right">Revenue</th></tr></thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr>
                                <td class="font-medium">{{ $category->category }}</td>
                                <td>{{ (int) $category->units }}</td>
                                <td class="text-right font-semibold">₱{{ number_format((float) $category->revenue, 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-10 text-center text-sm text-cocoa">No paid categories in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canvas = document.getElementById('reportChart');
            if (!canvas || !window.Chart) return;

            const daily = @json($daily);
            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: daily.map((day) => day.label),
                    datasets: [
                        {
                            label: 'Revenue',
                            data: daily.map((day) => day.revenue),
                            backgroundColor: 'rgba(185, 107, 124, 0.72)',
                            borderRadius: 4,
                            maxBarThickness: 22,
                        },
                        {
                            label: 'Orders',
                            data: daily.map((day) => day.orders),
                            type: 'line',
                            borderColor: '#2b211b',
                            backgroundColor: 'transparent',
                            tension: 0.3,
                            pointRadius: 0,
                            borderWidth: 1.5,
                            yAxisID: 'orders',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: '#6b5748', boxWidth: 9, font: { size: 10 } } } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: '#6b5748', font: { size: 9 } } },
                        y: { beginAtZero: true, grid: { color: 'rgba(43,33,27,0.06)' }, ticks: { color: '#6b5748', font: { size: 9 }, callback: (value) => '₱' + value.toLocaleString() } },
                        orders: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { color: '#6b5748', font: { size: 9 }, precision: 0 } }
                    }
                }
            });
        });
    </script>
@endpush
