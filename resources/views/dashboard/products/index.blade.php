@extends('layouts.dashboard')

@section('title', 'Cakes')
@section('eyebrow', 'Catalogue')
@section('heading', 'Cakes')

@section('actions')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <form method="GET" action="{{ route('dashboard.products.index') }}" class="flex flex-wrap items-end gap-2">
            <div class="min-w-44 flex-1 sm:max-w-xs">
                <label for="q" class="sr-only">Search cakes</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}" class="input py-2 text-sm" placeholder="Search your cakes">
            </div>
            <div>
                <label for="category" class="sr-only">Category</label>
                <select name="category" id="category" class="input py-2 text-sm">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="sr-only">Status</label>
                <select name="status" id="status" class="input py-2 text-sm">
                    <option value="">Any status</option>
                    @foreach (['active' => 'Active', 'inactive' => 'Hidden', 'low_stock' => 'Low stock', 'out_of_stock' => 'Out of stock'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-secondary btn-sm">Filter</button>
        </form>
        <a href="{{ route('dashboard.products.create') }}" class="btn-primary btn-sm">
            @include('partials.icon', ['name' => 'plus', 'class' => 'h-4 w-4'])
            New cake
        </a>
    </div>
@endsection

@section('content')
    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Cake</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Visibility</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <img src="{{ $product->imageUrl() }}" alt="" class="h-11 w-11 rounded-xl object-cover">
                                    <div class="min-w-0">
                                        <a href="{{ route('dashboard.products.edit', $product) }}" class="font-semibold hover:text-rose">{{ $product->name }}</a>
                                        <p class="mt-0.5 text-xs text-cocoa">{{ $product->variants->count() }} {{ \Illuminate\Support\Str::plural('size', $product->variants->count()) }} · {{ $product->preparation_hours }}h prep</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-sm text-cocoa">{{ $product->category?->name ?? 'Uncategorised' }}</td>
                            <td class="font-medium">₱{{ number_format((float) $product->base_price, 0) }}</td>
                            <td>
                                <span class="badge {{ $product->stock_quantity === 0 ? 'bg-rose/10 text-rose ring-rose/20' : ($product->stock_quantity <= $product->low_stock_threshold ? 'bg-amber-50 text-amber-700 ring-amber-200' : 'bg-sage/10 text-sage ring-sage/20') }}">{{ $product->stock_quantity }} left</span>
                            </td>
                            <td>
                                <span class="badge {{ $product->is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-stone-100 text-stone-600 ring-stone-200' }}">{{ $product->is_active ? 'Live' : 'Hidden' }}</span>
                                @if ($product->is_featured)
                                    <span class="ml-1 badge bg-gold/10 text-yellow-800 ring-gold/20">Featured</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('dashboard.products.edit', $product) }}" class="btn-ghost btn-sm">Edit</a>
                                    <form method="POST" action="{{ route('dashboard.products.destroy', $product) }}" onsubmit="return confirm('Remove this cake from your storefront?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-ghost btn-sm text-rose">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-14 text-center">
                                <p class="font-display text-2xl font-semibold">No cakes here yet.</p>
                                <p class="mt-1 text-sm text-cocoa">Add your first cake and it will appear on your storefront immediately.</p>
                                <a href="{{ route('dashboard.products.create') }}" class="mt-4 btn-primary btn-sm">Add a cake</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $products->links() }}</div>
@endsection
