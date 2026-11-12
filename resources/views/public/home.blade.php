@extends('layouts.public')

@section('title', 'Cakelle — Every celebration deserves a cake with a story')
@section('description', 'Discover independent Filipino bakeries, design your perfect cake, and reserve a pickup or delivery with Cakelle.')

@section('content')
    <section class="relative isolate overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,_rgba(246,226,229,0.9),_transparent_55%),radial-gradient(circle_at_bottom_right,_rgba(243,235,224,0.9),_transparent_50%)]"></div>

        <div class="container-page relative z-10 grid items-center gap-14 py-16 lg:grid-cols-[1.05fr_0.95fr] lg:py-24">
            <div>
                <p class="eyebrow">{{ $cms['hero_eyebrow'] }}</p>
                <h1 class="mt-5 max-w-2xl font-display text-5xl font-semibold leading-[1.05] tracking-tight sm:text-6xl lg:text-7xl">
                    {{ $cms['hero_heading'] }}
                </h1>
                <p class="mt-6 max-w-xl text-base leading-7 text-cocoa sm:text-lg">
                    {{ $cms['hero_subheading'] }}
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="{{ $cms['hero_primary_url'] }}" class="btn-primary">{{ $cms['hero_primary_label'] }}
                        <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.64l-3.2-3.2a.75.75 0 1 1 1.06-1.06l4.5 4.5a.75.75 0 0 1 0 1.06l-4.5 4.5a.75.75 0 1 1-1.06-1.06l3.2-3.2H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" /></svg>
                    </a>
                    <a href="{{ $cms['hero_secondary_url'] }}" class="btn-secondary">{{ $cms['hero_secondary_label'] }}</a>
                </div>

                <div class="mt-10 grid max-w-lg grid-cols-2 gap-x-8 gap-y-5 sm:grid-cols-4">
                    <div>
                        <p class="font-display text-3xl font-semibold">{{ number_format($stats['bakeries']) }}</p>
                        <p class="mt-1 text-xs font-medium uppercase tracking-[0.12em] text-cocoa/70">Bakeries</p>
                    </div>
                    <div>
                        <p class="font-display text-3xl font-semibold">{{ number_format($stats['cakes']) }}</p>
                        <p class="mt-1 text-xs font-medium uppercase tracking-[0.12em] text-cocoa/70">Cakes</p>
                    </div>
                    <div>
                        <p class="font-display text-3xl font-semibold">{{ number_format($stats['orders']) }}</p>
                        <p class="mt-1 text-xs font-medium uppercase tracking-[0.12em] text-cocoa/70">Celebrations</p>
                    </div>
                    <div>
                        <p class="font-display text-3xl font-semibold">{{ $stats['rating'] > 0 ? $stats['rating'] : '—' }}</p>
                        <p class="mt-1 text-xs font-medium uppercase tracking-[0.12em] text-cocoa/70">Avg. rating</p>
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="absolute -left-5 top-10 hidden h-28 w-28 rounded-full bg-blush blur-2xl sm:block"></div>
                <div class="absolute -right-4 bottom-16 hidden h-32 w-32 rounded-full bg-parchment blur-2xl sm:block"></div>
                <div class="relative overflow-hidden rounded-[2.5rem] border border-white/70 bg-white/50 p-2 shadow-[0_40px_100px_-50px_rgba(43,33,27,0.5)]">
                    <img src="{{ $cms['hero_image'] }}" alt="A beautifully finished celebration cake" class="aspect-[4/5] w-full rounded-[2rem] object-cover">
                </div>

                <div class="absolute -bottom-5 left-4 right-4 rounded-2xl border border-ink/8 bg-white/95 p-4 shadow-xl backdrop-blur sm:left-8 sm:right-8">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" /></svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold">Payments verified by PayMongo</p>
                            <p class="text-xs text-cocoa">Your order is only confirmed after payment clears.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="container-page py-16">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">Browse by occasion</p>
                <h2 class="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">What are we celebrating?</h2>
            </div>
            <a href="{{ route('marketplace') }}" class="nav-link inline-flex items-center gap-1.5">All cakes
                <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.64l-3.2-3.2a.75.75 0 1 1 1.06-1.06l4.5 4.5a.75.75 0 0 1 0 1.06l-4.5 4.5a.75.75 0 1 1-1.06-1.06l3.2-3.2H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" /></svg>
            </a>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($categories as $category)
                <a href="{{ route('marketplace', ['category' => $category->slug]) }}" class="group relative overflow-hidden rounded-3xl border border-ink/8 bg-white p-6 transition hover:-translate-y-0.5 hover:border-rose/30">
                    <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-blush/60 transition group-hover:scale-125"></div>
                    <div class="relative">
                        <p class="font-display text-2xl font-semibold">{{ $category->name }}</p>
                        <p class="mt-1.5 text-sm text-cocoa">{{ $category->products_count }} {{ Str::plural('cake', $category->products_count) }}</p>
                        <span class="mt-8 inline-flex items-center gap-1.5 text-sm font-semibold text-rose">Explore
                            <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.64l-3.2-3.2a.75.75 0 1 1 1.06-1.06l4.5 4.5a.75.75 0 0 1 0 1.06l-4.5 4.5a.75.75 0 1 1-1.06-1.06l3.2-3.2H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" /></svg>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    <section class="container-page py-16">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">Made by independent bakers</p>
                <h2 class="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">Cakes worth planning around.</h2>
            </div>
            <a href="{{ route('marketplace') }}" class="btn-secondary">Browse the marketplace</a>
        </div>

        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($featuredProducts as $product)
                @include('partials.product-card', ['product' => $product])
            @empty
                <div class="card p-8 text-sm text-cocoa sm:col-span-2 lg:col-span-4">Our bakers are adding their cakes. Check back shortly.</div>
            @endforelse
        </div>
    </section>

    <section class="border-y border-ink/8 bg-parchment/45 py-16">
        <div class="container-page">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">The bakeries</p>
                    <h2 class="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">Small studios. Serious craft.</h2>
                </div>
                <a href="{{ route('shops.index') }}" class="nav-link inline-flex items-center gap-1.5">Meet every bakery
                    <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.64l-3.2-3.2a.75.75 0 1 1 1.06-1.06l4.5 4.5a.75.75 0 0 1 0 1.06l-4.5 4.5a.75.75 0 1 1-1.06-1.06l3.2-3.2H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" /></svg>
                </a>
            </div>

            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($featuredShops as $shop)
                    <a href="{{ route('shops.show', $shop) }}" class="group card overflow-hidden">
                        <div class="relative aspect-[5/3] overflow-hidden bg-parchment">
                            <img src="{{ $shop->coverUrl() }}" alt="{{ $shop->name }}" loading="lazy" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-ink/80 to-transparent p-4 pt-12">
                                <p class="font-display text-xl font-semibold text-cream">{{ $shop->name }}</p>
                                <p class="text-xs text-cream/75">{{ $shop->city }}{{ $shop->province ? ', '.$shop->province : '' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between p-4 text-sm">
                            <span class="text-cocoa">{{ $shop->products_count }} {{ Str::plural('cake', $shop->products_count) }}</span>
                            <span class="inline-flex items-center gap-1 text-gold">
                                <svg viewBox="0 0 20 20" class="h-4 w-4 fill-current" aria-hidden="true"><path d="M10 1.6l2.47 5.01 5.53.8-4 3.9.94 5.51L10 14.22 5.06 16.8 6 11.29l-4-3.9 5.53-.8L10 1.6z" /></svg>
                                <span class="font-semibold text-ink">{{ number_format((float) $shop->rating, 1) }}</span>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="container-page grid gap-12 py-20 lg:grid-cols-2 lg:items-center">
        <div class="relative order-2 lg:order-1">
            <div class="grid grid-cols-2 gap-4">
                <img src="https://images.unsplash.com/photo-1621303837174-89787a7d4729?auto=format&fit=crop&w=700&q=80" alt="Baker finishing a cake" class="aspect-[3/4] w-full rounded-[2rem] object-cover" loading="lazy">
                <div class="space-y-4 pt-10">
                    <img src="https://images.unsplash.com/photo-1535141192574-5d4897c12636?auto=format&fit=crop&w=700&q=80" alt="Slices of cake" class="aspect-square w-full rounded-[2rem] object-cover" loading="lazy">
                    <div class="rounded-[2rem] bg-ink p-6 text-cream">
                        <p class="font-display text-3xl font-semibold">72h</p>
                        <p class="mt-1 text-sm text-cream/70">Typical notice for a tiered celebration cake.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="order-1 lg:order-2">
            <p class="eyebrow">How Cakelle works</p>
            <h2 class="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">A slower, more thoughtful way to order a cake.</h2>
            <p class="mt-5 text-cocoa">We built Cakelle around the details that make a cake feel personal: the baker, the finish, the date it needs to be ready, and the person it is for.</p>

            <ol class="mt-8 space-y-5">
                @foreach ([
                    ['Find your baker', 'Browse real storefronts and compare cakes, finishes, and pickup or delivery options.'],
                    ['Make it yours', 'Choose a size, flavour, and message. Checkout validates the bakery schedule before you pay.'],
                    ['Pay securely', 'PayMongo verifies the payment. Your bakery sees a confirmed order, not a maybe.'],
                    ['Celebrate', 'Track every step from confirmed to ready, with one clear place for the details.'],
                ] as $index => [$title, $description])
                    <li class="flex gap-4">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-ink text-sm font-semibold text-cream">{{ $index + 1 }}</span>
                        <div>
                            <p class="font-semibold">{{ $title }}</p>
                            <p class="mt-1 text-sm leading-6 text-cocoa">{{ $description }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    @if ($cmsTestimonials->isNotEmpty() || $testimonials->isNotEmpty())
        <section class="border-y border-ink/8 bg-white/50 py-16">
            <div class="container-page">
                <div class="text-center">
                    <p class="eyebrow">From the table</p>
                    <h2 class="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">Celebrations, remembered.</h2>
                </div>

                <div class="mt-10 grid gap-5 lg:grid-cols-3">
                    @foreach ($cmsTestimonials->take(3) as $testimonial)
                        <figure class="card p-6">
                            @include('partials.stars', ['rating' => $testimonial['rating'] ?? 5])
                            <blockquote class="mt-4 font-display text-xl font-medium leading-8">“{{ $testimonial['quote'] }}”</blockquote>
                            <figcaption class="mt-5 text-sm">
                                <p class="font-semibold">{{ $testimonial['name'] }}</p>
                                <p class="text-cocoa">{{ $testimonial['detail'] }}</p>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="container-page py-16">
        <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr]">
            <div>
                <p class="eyebrow">Good to know</p>
                <h2 class="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">Before you order.</h2>
                <p class="mt-4 text-cocoa">A few honest answers about schedules, payments, and the people behind the cakes.</p>
                <a href="{{ route('faqs') }}" class="mt-6 inline-flex btn-secondary">All FAQs</a>
            </div>

            <div class="space-y-3">
                @foreach ($cmsFaqs as $faq)
                    <details class="group card p-5" {{ $loop->first ? 'open' : '' }}>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">
                            {{ $faq['question'] }}
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full border border-ink/10 text-cocoa transition group-open:rotate-45">
                                <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>
                            </span>
                        </summary>
                        <p class="mt-3 text-sm leading-7 text-cocoa">{{ $faq['answer'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    <section class="container-page pb-20">
        <div class="relative overflow-hidden rounded-[2.5rem] bg-ink px-6 py-14 text-cream sm:px-12 lg:px-16">
            <div class="absolute -right-20 -top-20 h-72 w-72 rounded-full bg-rose/20 blur-3xl"></div>
            <div class="absolute -bottom-24 left-10 h-64 w-64 rounded-full bg-gold/15 blur-3xl"></div>
            <div class="relative grid gap-8 lg:grid-cols-[1.4fr_0.6fr] lg:items-center">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cream/60">For independent bakers</p>
                    <h2 class="mt-4 max-w-2xl font-display text-4xl font-semibold leading-tight sm:text-5xl">Bring your cakes to people who are looking for exactly this.</h2>
                    <p class="mt-5 max-w-xl text-cream/70">A considered storefront, order management, inventory, and secure payments — without giving up the way you bake.</p>
                </div>
                <div class="flex flex-col gap-3 lg:items-end">
                    <a href="{{ route('shop.register') }}" class="btn bg-cream text-ink hover:bg-parchment">Apply to sell</a>
                    <a href="{{ route('about') }}" class="text-sm font-semibold text-cream/70 hover:text-cream">Read our story</a>
                </div>
            </div>
        </div>
    </section>
@endsection
