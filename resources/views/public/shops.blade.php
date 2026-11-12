@extends('layouts.public')

@section('title', 'Bakeries — Cakelle')

@section('content')
    <section class="border-b border-ink/8 bg-parchment/40">
        <div class="container-page py-12 lg:py-16">
            <p class="eyebrow">The bakeries</p>
            <div class="mt-3 flex flex-wrap items-end justify-between gap-6">
                <div>
                    <h1 class="font-display text-4xl font-semibold tracking-tight sm:text-5xl">Independent studios, one standard.</h1>
                    <p class="mt-3 max-w-2xl text-cocoa">Every bakery on Cakelle is reviewed before it opens. Browse their storefronts, then order directly from the people who bake.</p>
                </div>

                <form method="GET" action="{{ route('shops.index') }}" class="flex w-full gap-2 sm:w-auto">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search bakeries or cities" class="input sm:w-72">
                    <button type="submit" class="btn-primary">Search</button>
                </form>
            </div>
        </div>
    </section>

    <section class="container-page py-12">
        <div class="flex items-center justify-between gap-4">
            <p class="text-sm text-cocoa">{{ $shops->total() }} {{ \Illuminate\Support\Str::plural('bakery', $shops->total()) }} on Cakelle</p>
            @if ($search !== '')
                <a href="{{ route('shops.index') }}" class="text-sm font-semibold text-rose hover:underline">Clear search</a>
            @endif
        </div>

        <div class="mt-6 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($shops as $shop)
                <article class="group overflow-hidden rounded-3xl border border-ink/8 bg-white shadow-[0_18px_50px_-30px_rgba(43,33,27,0.35)] transition hover:-translate-y-0.5 hover:shadow-[0_26px_60px_-30px_rgba(43,33,27,0.4)]">
                    <a href="{{ route('shops.show', $shop) }}" class="block">
                        <div class="relative aspect-[16/9] overflow-hidden bg-parchment">
                            <img src="{{ $shop->coverUrl() }}" alt="{{ $shop->name }}" loading="lazy" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                            @if ($shop->is_featured)
                                <span class="absolute left-3 top-3 badge bg-cream/95 text-cocoa ring-ink/10">Featured bakery</span>
                            @endif
                        </div>
                    </a>

                    <div class="p-5">
                        <div class="flex items-start gap-3">
                            <img src="{{ $shop->logoUrl() }}" alt="" class="h-12 w-12 rounded-full object-cover ring-1 ring-ink/10">
                            <div class="min-w-0 flex-1">
                                <h2 class="font-display text-xl font-semibold leading-snug">
                                    <a href="{{ route('shops.show', $shop) }}" class="hover:text-rose">{{ $shop->name }}</a>
                                </h2>
                                <p class="mt-0.5 text-xs text-cocoa">{{ $shop->city }}{{ $shop->province ? ', '.$shop->province : '' }}</p>
                            </div>
                            <span class="inline-flex items-center gap-1 rounded-full bg-parchment px-2.5 py-1 text-xs font-semibold">
                                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 fill-gold" aria-hidden="true"><path d="M10 1.6l2.47 5.01 5.53.8-4 3.9.94 5.51L10 14.22 5.06 16.8 6 11.29l-4-3.9 5.53-.8L10 1.6z" /></svg>
                                {{ number_format((float) $shop->rating, 1) }}
                            </span>
                        </div>

                        <p class="mt-4 line-clamp-2 text-sm leading-6 text-cocoa">{{ $shop->tagline ?: $shop->description }}</p>

                        <div class="mt-5 flex items-center justify-between gap-3 text-sm">
                            <span class="text-cocoa">{{ $shop->products_count }} {{ \Illuminate\Support\Str::plural('cake', $shop->products_count) }}</span>
                            <a href="{{ route('shops.show', $shop) }}" class="btn-secondary btn-sm">Visit bakery</a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="card p-10 text-center md:col-span-2 xl:col-span-3">
                    <p class="font-display text-3xl font-semibold">No bakeries found.</p>
                    <p class="mt-2 text-sm text-cocoa">Try another city or clear the search.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-10">{{ $shops->links() }}</div>
    </section>
@endsection
