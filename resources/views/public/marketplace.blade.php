@extends('layouts.public')

@section('title', 'Explore cakes — Cakelle')

@section('content')
    <section class="border-b border-ink/8 bg-parchment/40">
        <div class="container-page py-12 lg:py-16">
            <p class="eyebrow">The marketplace</p>
            <div class="mt-3 flex flex-wrap items-end justify-between gap-5">
                <div>
                    <h1 class="font-display text-4xl font-semibold tracking-tight sm:text-5xl">Find the cake for the moment.</h1>
                    <p class="mt-3 max-w-2xl text-cocoa">Independent bakeries, considered finishes, and schedules that actually work. Filter by occasion, city, or budget.</p>
                </div>
                <p class="text-sm text-cocoa">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('cake', $products->total()) }} available</p>
            </div>

            <form method="GET" action="{{ route('marketplace') }}" class="mt-8 grid gap-3 rounded-3xl border border-ink/8 bg-white/80 p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-[1.6fr_1fr_1fr_1fr_auto] lg:items-end">
                <div>
                    <label for="q" class="label">Search</label>
                    <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cake, flavour, or bakery" class="input">
                </div>
                <div>
                    <label for="category" class="label">Category</label>
                    <select name="category" id="category" class="input">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected(($filters['category'] ?? '') === $category->slug)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="city" class="label">City</label>
                    <select name="city" id="city" class="input">
                        <option value="">Anywhere</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city }}" @selected(($filters['city'] ?? '') === $city)>{{ $city }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="sort" class="label">Sort</label>
                    <select name="sort" id="sort" class="input">
                        <option value="recommended" @selected(($filters['sort'] ?? '') === 'recommended')>Recommended</option>
                        <option value="newest" @selected(($filters['sort'] ?? '') === 'newest')>Newest</option>
                        <option value="price_low" @selected(($filters['sort'] ?? '') === 'price_low')>Price: low to high</option>
                        <option value="price_high" @selected(($filters['sort'] ?? '') === 'price_high')>Price: high to low</option>
                        <option value="rating" @selected(($filters['sort'] ?? '') === 'rating')>Bakery rating</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary flex-1">Filter</button>
                    <a href="{{ route('marketplace') }}" class="btn-ghost" aria-label="Clear filters">
                        <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                    </a>
                </div>
            </form>
        </div>
    </section>

    <section class="container-page grid gap-10 py-12 lg:grid-cols-[16rem_1fr]">
        <aside class="hidden lg:block">
            <div class="sticky top-28 space-y-6">
                <div class="card p-5">
                    <p class="eyebrow">Refine</p>
                    <form method="GET" action="{{ route('marketplace') }}" class="mt-4 space-y-4">
                        <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                        <input type="hidden" name="category" value="{{ $filters['category'] ?? '' }}">
                        <input type="hidden" name="city" value="{{ $filters['city'] ?? '' }}">
                        <input type="hidden" name="sort" value="{{ $filters['sort'] ?? '' }}">
                        <div>
                            <p class="label">Budget</p>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="number" name="min_price" min="0" value="{{ $filters['min_price'] ?? '' }}" placeholder="Min" class="input">
                                <input type="number" name="max_price" min="0" value="{{ $filters['max_price'] ?? '' }}" placeholder="Max" class="input">
                            </div>
                        </div>
                        <div>
                            <p class="label">Fulfillment</p>
                            <label class="flex items-center gap-2 text-sm text-cocoa">
                                <input type="radio" name="fulfillment" value="" @checked(empty($filters['fulfillment'])) class="border-ink/20 text-rose focus:ring-rose">
                                Any
                            </label>
                            <label class="mt-2 flex items-center gap-2 text-sm text-cocoa">
                                <input type="radio" name="fulfillment" value="pickup" @checked(($filters['fulfillment'] ?? '') === 'pickup') class="border-ink/20 text-rose focus:ring-rose">
                                Pickup
                            </label>
                            <label class="mt-2 flex items-center gap-2 text-sm text-cocoa">
                                <input type="radio" name="fulfillment" value="delivery" @checked(($filters['fulfillment'] ?? '') === 'delivery') class="border-ink/20 text-rose focus:ring-rose">
                                Delivery
                            </label>
                        </div>
                        <button type="submit" class="btn-primary w-full">Apply</button>
                    </form>
                </div>

                <div class="rounded-3xl bg-ink p-5 text-cream">
                    <p class="font-display text-2xl font-semibold">Not sure where to start?</p>
                    <p class="mt-2 text-sm leading-6 text-cream/70">Browse by bakery instead. Every studio has its own point of view.</p>
                    <a href="{{ route('shops.index') }}" class="mt-4 inline-flex text-sm font-semibold text-cream underline decoration-cream/30 underline-offset-4 hover:decoration-cream">Meet the bakers</a>
                </div>
            </div>
        </aside>

        <div>
            @if ($products->isEmpty())
                <div class="card p-10 text-center">
                    <p class="font-display text-3xl font-semibold">No cakes match that search.</p>
                    <p class="mt-2 text-sm text-cocoa">Try a broader category, another city, or clear the filters.</p>
                    <a href="{{ route('marketplace') }}" class="mt-6 btn-secondary">Clear filters</a>
                </div>
            @else
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($products as $product)
                        @include('partials.product-card', ['product' => $product])
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
