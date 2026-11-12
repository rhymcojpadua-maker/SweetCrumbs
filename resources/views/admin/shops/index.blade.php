@extends('layouts.admin')

@section('title', 'Bakeries')
@section('eyebrow', 'Marketplace')
@section('heading', 'Bakeries')

@section('actions')
    <form method="GET" action="{{ route('admin.shops.index') }}" class="flex flex-wrap items-end gap-2">
        <div class="min-w-44 flex-1 sm:max-w-xs">
            <label for="q" class="sr-only">Search bakeries</label>
            <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}" class="input py-2 text-sm" placeholder="Name, email, or city">
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
    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Bakery</th>
                        <th>Owner</th>
                        <th>Catalogue</th>
                        <th>Orders</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shops as $shop)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <img src="{{ $shop->logoUrl() }}" alt="" class="h-10 w-10 rounded-full object-cover">
                                    <div>
                                        <a href="{{ route('admin.shops.show', $shop) }}" class="font-semibold hover:text-rose">{{ $shop->name }}</a>
                                        <p class="text-xs text-cocoa">{{ $shop->city }}, {{ $shop->province }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <p class="text-sm">{{ $shop->owner?->name }}</p>
                                <p class="text-xs text-cocoa">{{ $shop->owner?->email }}</p>
                            </td>
                            <td class="text-sm">{{ $shop->products_count }} cakes</td>
                            <td class="text-sm">{{ $shop->orders_count }}</td>
                            <td><span class="badge {{ $shop->status->badgeClasses() }}">{{ $shop->status->label() }}</span></td>
                            <td class="text-right"><a href="{{ route('admin.shops.show', $shop) }}" class="btn-ghost btn-sm">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-14 text-center text-sm text-cocoa">No bakeries match this view.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $shops->links() }}</div>
@endsection
