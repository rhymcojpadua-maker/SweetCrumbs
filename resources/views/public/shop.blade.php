@extends('layouts.public')

@section('title', $shop->name.' — SweetCrumbs')
@section('description', \Illuminate\Support\Str::limit(strip_tags($shop->description), 155))

@section('content')
    <section class="relative">
        <div class="h-56 overflow-hidden bg-parchment sm:h-72 lg:h-80">
            <img src="{{ $shop->coverUrl() }}" alt="{{ $shop->name }}" class="h-full w-full object-cover">
        </div>
        <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-cream to-transparent"></div>

        <div class="container-page relative -mt-16">
            <div class="card p-6 sm:p-8">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
                    <img src="{{ $shop->logoUrl() }}" alt="{{ $shop->name }}" class="h-20 w-20 rounded-2xl object-cover ring-4 ring-cream">

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ $shop->name }}</h1>
                            @if ($shop->is_featured)
                                <span class="badge bg-blush text-rose ring-rose/15">Featured</span>
                            @endif
                        </div>
                        <p class="mt-2 max-w-2xl text-cocoa">{{ $shop->tagline }}</p>

                        <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-cocoa">
                            <span class="inline-flex items-center gap-1.5">
                                @include('partials.stars', ['rating' => (float) $shop->rating])
                                <span class="font-semibold text-ink">{{ number_format((float) $shop->rating, 1) }}</span>
                                <span>({{ $reviewCount }})</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="h-4 w-4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-5.4 7-11a7 7 0 1 0-14 0c0 5.6 7 11 7 11Z" /><circle cx="12" cy="10" r="2.5" /></svg>
                                {{ $shop->address }}, {{ $shop->city }}
                            </span>
                            @if ($shop->settings?->pickup_enabled)
                                <span class="badge bg-emerald-50 text-emerald-700 ring-emerald-200">Pickup</span>
                            @endif
                            @if ($shop->settings?->delivery_enabled)
                                <span class="badge bg-sky-50 text-sky-700 ring-sky-200">Delivery</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-wrap gap-2">
                        <a href="mailto:{{ $shop->email }}" class="btn-secondary btn-sm">Contact bakery</a>
                        <a href="#menu" class="btn-primary btn-sm">View menu</a>
                    </div>
                </div>

                <p class="mt-6 max-w-4xl whitespace-pre-line text-sm leading-7 text-cocoa">{{ $shop->description }}</p>
            </div>
        </div>
    </section>

    <section id="menu" class="container-page grid gap-10 py-12 lg:grid-cols-[16rem_1fr]">
        <aside class="space-y-5">
            <div class="card p-5">
                <p class="eyebrow">Find a cake</p>
                <form method="GET" action="{{ route('shops.show', $shop) }}" class="mt-4 space-y-3">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search this bakery" class="input">
                    <div>
                        <label for="category" class="label">Category</label>
                        <select name="category" id="category" class="input">
                            <option value="">All cakes</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }} ({{ $category->products_count }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="sort" class="label">Sort</label>
                        <select name="sort" id="sort" class="input">
                            <option value="recommended" @selected(request('sort') === 'recommended')>Recommended</option>
                            <option value="price_low" @selected(request('sort') === 'price_low')>Price: low to high</option>
                            <option value="price_high" @selected(request('sort') === 'price_high')>Price: high to low</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary w-full">Apply</button>
                </form>
            </div>

            <div class="card p-5 text-sm">
                <p class="eyebrow">Bakery details</p>
                <dl class="mt-4 space-y-3">
                    <div>
                        <dt class="text-xs text-cocoa/70">Address</dt>
                        <dd class="mt-0.5 font-medium">{{ $shop->address }}, {{ $shop->city }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-cocoa/70">Email</dt>
                        <dd class="mt-0.5"><a href="mailto:{{ $shop->email }}" class="font-medium text-rose hover:underline">{{ $shop->email }}</a></dd>
                    </div>
                    <div>
                        <dt class="text-xs text-cocoa/70">Phone</dt>
                        <dd class="mt-0.5 font-medium">{{ $shop->phone }}</dd>
                    </div>
                    @if ($shop->settings?->delivery_fee)
                        <div>
                            <dt class="text-xs text-cocoa/70">Delivery fee</dt>
                            <dd class="mt-0.5 font-medium">₱{{ number_format((float) $shop->settings->delivery_fee, 0) }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </aside>

        <div>
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-display text-3xl font-semibold">The menu</h2>
                <p class="text-sm text-cocoa">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('cake', $products->total()) }}</p>
            </div>

            @if ($products->isEmpty())
                <div class="mt-6 card p-8 text-sm text-cocoa">This bakery has not listed cakes in this category yet.</div>
            @else
                <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($products as $product)
                        @include('partials.product-card', ['product' => $product])
                    @endforeach
                </div>
                <div class="mt-10">{{ $products->links() }}</div>
            @endif

            <section class="mt-16">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-3xl font-semibold">What customers say</h2>
                    <p class="text-sm text-cocoa">{{ number_format((float) $shop->rating, 1) }} average</p>
                </div>

                <div class="mt-6 grid gap-8 lg:grid-cols-[18rem_1fr]">
                    <div class="card p-5">
                        @foreach ($ratingBreakdown as $stars => $count)
                            <div class="flex items-center gap-3 py-1">
                                <span class="w-10 text-xs font-semibold text-cocoa">{{ $stars }} star</span>
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-parchment">
                                    <div class="h-full rounded-full bg-gold" style="width: {{ $reviewCount > 0 ? ($count / $reviewCount) * 100 : 0 }}%"></div>
                                </div>
                                <span class="w-6 text-right text-xs text-cocoa">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        @forelse ($reviews as $review)
                            <article class="card-flat p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $review->customer?->avatarUrl() }}" alt="" class="h-9 w-9 rounded-full object-cover">
                                        <div>
                                            <p class="text-sm font-semibold">{{ $review->customer?->name ?? 'SweetCrumbs customer' }}</p>
                                            <p class="text-xs text-cocoa">{{ $review->created_at->format('M j, Y') }}</p>
                                        </div>
                                    </div>
                                    @include('partials.stars', ['rating' => $review->rating])
                                </div>
                                @if ($review->comment)
                                    <p class="mt-3 text-sm leading-6 text-cocoa">{{ $review->comment }}</p>
                                @endif
                            </article>
                        @empty
                            <div class="card-flat p-6 text-sm text-cocoa sm:col-span-2">The first review is waiting to be written.</div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </section>
@endsection
