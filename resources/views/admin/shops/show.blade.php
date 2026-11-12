@extends('layouts.admin')

@section('title', $shop->name)
@section('eyebrow', 'Bakery detail')
@section('heading', $shop->name)

@section('actions')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="badge {{ $shop->status->badgeClasses() }}">{{ $shop->status->label() }}</span>
            <span class="text-xs text-cocoa">Joined {{ $shop->created_at->format('M j, Y') }}</span>
        </div>
        <a href="{{ route('admin.shops.index') }}" class="btn-secondary btn-sm">All bakeries</a>
    </div>
@endsection

@section('content')
    <div class="grid gap-5 xl:grid-cols-[1fr_22rem]">
        <div class="space-y-5">
            <section class="card overflow-hidden">
                <div class="relative h-40 bg-ink">
                    <img src="{{ $shop->coverUrl() }}" alt="" class="h-full w-full object-cover opacity-75">
                </div>
                <div class="-mt-8 px-5 pb-5 sm:px-6">
                    <div class="flex flex-wrap items-end gap-3">
                        <img src="{{ $shop->logoUrl() }}" alt="" class="h-16 w-16 rounded-2xl border-4 border-white object-cover">
                        <div class="pb-1">
                            <h2 class="font-display text-2xl font-semibold">{{ $shop->name }}</h2>
                            <p class="text-sm text-cocoa">{{ $shop->tagline }}</p>
                        </div>
                    </div>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-cocoa">{{ $shop->description }}</p>
                    <div class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                        <p><span class="text-cocoa">Email:</span> {{ $shop->email }}</p>
                        <p><span class="text-cocoa">Phone:</span> {{ $shop->phone }}</p>
                        <p class="sm:col-span-2"><span class="text-cocoa">Address:</span> {{ $shop->address }}, {{ $shop->city }}, {{ $shop->province }} {{ $shop->postal_code }}</p>
                    </div>
                </div>
            </section>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="metric"><p class="metric-label">Paid revenue</p><p class="metric-value mt-3">₱{{ number_format($revenue, 0) }}</p></div>
                <div class="metric"><p class="metric-label">Orders</p><p class="metric-value mt-3">{{ $shop->orders_count }}</p></div>
                <div class="metric"><p class="metric-label">Cakes</p><p class="metric-value mt-3">{{ $shop->products_count }}</p></div>
                <div class="metric"><p class="metric-label">Customers</p><p class="metric-value mt-3">{{ $shop->customers_count }}</p></div>
            </div>

            <section class="card overflow-hidden">
                <div class="border-b border-ink/8 px-5 py-4 sm:px-6">
                    <h2 class="font-display text-xl font-semibold">Recent orders</h2>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th class="text-right">Total</th></tr></thead>
                        <tbody>
                            @forelse ($recentOrders as $order)
                                <tr>
                                    <td><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold hover:text-rose">{{ $order->order_number }}</a><p class="text-xs text-cocoa">{{ $order->placed_at?->format('M j, Y') }}</p></td>
                                    <td class="text-sm">{{ $order->customer_name }}</td>
                                    <td><span class="badge {{ $order->status->badgeClasses() }}">{{ $order->status->label() }}</span></td>
                                    <td class="text-right font-semibold">₱{{ number_format((float) $order->total, 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-10 text-center text-sm text-cocoa">No orders yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-xl font-semibold">Fulfillment settings</h2>
                @if ($shop->settings)
                    <div class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                        <div><p class="label">Pickup</p><p class="font-medium">{{ $shop->settings->pickup_enabled ? 'Enabled' : 'Disabled' }}</p></div>
                        <div><p class="label">Delivery</p><p class="font-medium">{{ $shop->settings->delivery_enabled ? 'Enabled' : 'Disabled' }}</p></div>
                        <div><p class="label">Delivery fee</p><p class="font-medium">₱{{ number_format((float) $shop->settings->delivery_fee, 0) }}</p></div>
                        <div><p class="label">Advance notice</p><p class="font-medium">{{ $shop->settings->advance_notice_hours }} hours</p></div>
                        <div><p class="label">Time slots</p><p class="font-medium">{{ implode(', ', $shop->settings->time_slots ?? []) ?: '—' }}</p></div>
                        <div><p class="label">Max orders / day</p><p class="font-medium">{{ $shop->settings->max_orders_per_day }}</p></div>
                    </div>
                @else
                    <p class="mt-2 text-sm text-cocoa">This bakery has not saved fulfillment settings yet.</p>
                @endif
            </section>
        </div>

        <aside class="space-y-5">
            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-xl font-semibold">Moderation</h2>
                <div class="mt-4 space-y-3">
                    @if ($shop->status === \App\Enums\ShopStatus::Pending || $shop->status === \App\Enums\ShopStatus::Rejected)
                        <form method="POST" action="{{ route('admin.shops.approve', $shop) }}" onsubmit="return confirm('Approve this bakery and open the storefront?');">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn-primary w-full">Approve bakery</button>
                        </form>
                    @endif

                    @if ($shop->status === \App\Enums\ShopStatus::Suspended)
                        <form method="POST" action="{{ route('admin.shops.activate', $shop) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn-primary w-full">Reactivate bakery</button>
                        </form>
                    @endif
                </div>

                @if ($shop->status !== \App\Enums\ShopStatus::Rejected)
                    <details class="mt-4 rounded-xl border border-ink/10 p-3">
                        <summary class="cursor-pointer text-sm font-semibold text-cocoa">Reject application</summary>
                        <form method="POST" action="{{ route('admin.shops.reject', $shop) }}" class="mt-3">
                            @csrf @method('PATCH')
                            <textarea name="reason" rows="3" required minlength="10" maxlength="1000" class="input text-sm" placeholder="Reason for the owner"></textarea>
                            <button type="submit" class="mt-2 btn-danger btn-sm w-full">Send rejection</button>
                        </form>
                    </details>
                @endif

                @if ($shop->status === \App\Enums\ShopStatus::Active)
                    <details class="mt-3 rounded-xl border border-ink/10 p-3">
                        <summary class="cursor-pointer text-sm font-semibold text-cocoa">Suspend bakery</summary>
                        <form method="POST" action="{{ route('admin.shops.suspend', $shop) }}" class="mt-3">
                            @csrf @method('PATCH')
                            <textarea name="reason" rows="3" maxlength="1000" class="input text-sm" placeholder="Reason shared with the owner"></textarea>
                            <button type="submit" class="mt-2 btn-danger btn-sm w-full">Suspend</button>
                        </form>
                    </details>
                @endif

                @if ($shop->rejection_reason)
                    <div class="mt-4 rounded-xl bg-rose/5 px-3 py-2 text-xs leading-5 text-cocoa">
                        <span class="font-semibold text-ink">Rejection reason:</span> {{ $shop->rejection_reason }}
                    </div>
                @endif
            </section>

            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-xl font-semibold">Owner</h2>
                <div class="mt-3 flex items-center gap-3">
                    <img src="{{ $shop->owner?->avatarUrl() }}" alt="" class="h-10 w-10 rounded-full object-cover">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ $shop->owner?->name }}</p>
                        <p class="truncate text-xs text-cocoa">{{ $shop->owner?->email }}</p>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="badge {{ $shop->owner?->is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-stone-100 text-stone-600 ring-stone-200' }}">{{ $shop->owner?->is_active ? 'Active account' : 'Deactivated' }}</span>
                    <span class="badge bg-parchment text-cocoa ring-ink/10">{{ $shop->reviews_count }} reviews</span>
                </div>
                <a href="{{ route('admin.users.index', ['q' => $shop->owner?->email]) }}" class="mt-4 btn-secondary btn-sm w-full">Find owner account</a>
            </section>

            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-xl font-semibold">Storefront</h2>
                <p class="mt-1 break-all font-mono text-xs text-cocoa">{{ route('shops.show', $shop) }}</p>
                <a href="{{ route('shops.show', $shop) }}" target="_blank" rel="noopener" class="mt-3 btn-secondary btn-sm w-full">Open public page</a>
            </section>
        </aside>
    </div>
@endsection
