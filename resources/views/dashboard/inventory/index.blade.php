@extends('layouts.dashboard')

@section('title', 'Inventory')
@section('eyebrow', 'Operations')
@section('heading', 'Inventory')

@section('actions')
    <form method="GET" action="{{ route('dashboard.inventory.index') }}" class="flex flex-wrap items-end gap-2">
        <div class="min-w-44 flex-1 sm:max-w-xs">
            <label for="q" class="sr-only">Search cakes</label>
            <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}" class="input py-2 text-sm" placeholder="Search cakes">
        </div>
        <div>
            <label for="stock" class="sr-only">Stock level</label>
            <select name="stock" id="stock" class="input py-2 text-sm">
                <option value="all">All stock</option>
                <option value="low" @selected(($filters['stock'] ?? null) === 'low')>Low stock</option>
                <option value="out" @selected(($filters['stock'] ?? null) === 'out')>Out of stock</option>
            </select>
        </div>
        <button type="submit" class="btn-secondary btn-sm">Filter</button>
    </form>
@endsection

@section('content')
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="metric">
            <p class="metric-label">Low stock</p>
            <p class="metric-value mt-3">{{ $summary['low'] }}</p>
        </div>
        <div class="metric">
            <p class="metric-label">Out of stock</p>
            <p class="metric-value mt-3">{{ $summary['out'] }}</p>
        </div>
        <div class="metric">
            <p class="metric-label">Total units</p>
            <p class="metric-value mt-3">{{ number_format($summary['units']) }}</p>
        </div>
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-[1.4fr_1fr]">
        <section class="card overflow-hidden">
            <div class="border-b border-ink/8 px-5 py-4 sm:px-6">
                <h2 class="font-display text-xl font-semibold">Stock levels</h2>
                <p class="mt-1 text-xs text-cocoa">Adjustments are recorded with a reason for your own records.</p>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Cake</th>
                            <th>On hand</th>
                            <th>Adjust</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td>
                                    <p class="font-medium">{{ $product->name }}</p>
                                    <p class="text-xs text-cocoa">Low stock threshold {{ $product->low_stock_threshold }}</p>
                                </td>
                                <td>
                                    <span class="badge {{ $product->stock_quantity === 0 ? 'bg-rose/10 text-rose ring-rose/20' : ($product->stock_quantity <= $product->low_stock_threshold ? 'bg-amber-50 text-amber-700 ring-amber-200' : 'bg-sage/10 text-sage ring-sage/20') }}">{{ $product->stock_quantity }}</span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('dashboard.inventory.adjust', $product) }}" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        <input type="number" name="new_quantity" value="{{ $product->stock_quantity }}" min="0" max="100000" required class="input w-24 py-1.5 text-sm">
                                        <input type="text" name="reason" required maxlength="255" placeholder="Reason" class="input w-28 py-1.5 text-sm">
                                        <button type="submit" class="btn-secondary btn-sm">Set</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-12 text-center text-sm text-cocoa">No cakes match this filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-ink/8 px-5 py-4 sm:px-6">{{ $products->links() }}</div>
        </section>

        <section class="card overflow-hidden">
            <div class="border-b border-ink/8 px-5 py-4 sm:px-6">
                <h2 class="font-display text-xl font-semibold">Movement history</h2>
                <p class="mt-1 text-xs text-cocoa">Reservations, releases, and manual counts.</p>
            </div>
            <ol class="divide-y divide-ink/8">
                @forelse ($movements as $movement)
                    <li class="px-5 py-3.5 sm:px-6">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium">{{ $movement->product?->name ?? 'Deleted cake' }}</p>
                                <p class="mt-0.5 text-xs text-cocoa">{{ $movement->reason }}@if ($movement->order) · {{ $movement->order->order_number }}@endif</p>
                            </div>
                            <span class="font-semibold {{ $movement->quantity > 0 ? 'text-emerald-700' : 'text-rose' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</span>
                        </div>
                        <p class="mt-1 text-[10px] uppercase tracking-wide text-cocoa/60">{{ $movement->created_at->format('M j, Y g:i A') }}@if ($movement->user) · {{ $movement->user->name }}@endif</p>
                    </li>
                @empty
                    <li class="px-5 py-12 text-center text-sm text-cocoa">No inventory movements yet.</li>
                @endforelse
            </ol>
            <div class="border-t border-ink/8 px-5 py-4 sm:px-6">{{ $movements->links() }}</div>
        </section>
    </div>
@endsection
