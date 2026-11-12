@extends('layouts.dashboard')

@section('title', 'Customers')
@section('eyebrow', 'Relationships')
@section('heading', 'Customers')

@section('actions')
    <form method="GET" action="{{ route('dashboard.customers.index') }}" class="flex flex-wrap items-end gap-2">
        <div class="min-w-52 flex-1 sm:max-w-sm">
            <label for="q" class="sr-only">Search customers</label>
            <input type="search" name="q" id="q" value="{{ $search }}" class="input py-2 text-sm" placeholder="Name, email, or phone">
        </div>
        <button type="submit" class="btn-secondary btn-sm">Search</button>
        @if ($search !== '')
            <a href="{{ route('dashboard.customers.index') }}" class="btn-ghost btn-sm">Clear</a>
        @endif
    </form>
@endsection

@section('content')
    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Orders</th>
                        <th>Total spent</th>
                        <th>Last order</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td>
                                <p class="font-semibold">{{ $customer->name }}</p>
                                <p class="text-xs text-cocoa">{{ $customer->email }} · {{ $customer->phone }}</p>
                            </td>
                            <td class="font-medium">{{ $customer->order_count }}</td>
                            <td class="font-medium">₱{{ number_format((float) $customer->total_spent, 0) }}</td>
                            <td class="text-sm text-cocoa">{{ $customer->last_order_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="text-right"><a href="{{ route('dashboard.customers.show', $customer) }}" class="btn-ghost btn-sm">History</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-14 text-center text-sm text-cocoa">No customers match this search yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $customers->links() }}</div>
@endsection
