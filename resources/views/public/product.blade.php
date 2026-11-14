@extends('layouts.public')

@section('title', $product->name.' — '.$product->shop->name.' — SweetCrumbs')
@section('description', \Illuminate\Support\Str::limit(strip_tags($product->description), 155))

@section('content')
    @php
        $images = $product->images;
        $gallery = $images->map(fn ($image) => $image->url())->values();
        if ($gallery->isEmpty()) {
            $gallery = collect([$product->imageUrl()]);
        }
        $defaultVariant = $product->defaultVariant();
        $options = $product->customization_options ?? [];
    @endphp

    <div class="container-page py-8">
        <nav class="flex flex-wrap items-center gap-2 text-xs text-cocoa/70">
            <a href="{{ route('home') }}" class="hover:text-ink">Home</a>
            <span>/</span>
            <a href="{{ route('marketplace') }}" class="hover:text-ink">Cakes</a>
            <span>/</span>
            <a href="{{ route('shops.show', $product->shop) }}" class="hover:text-ink">{{ $product->shop->name }}</a>
            <span>/</span>
            <span class="truncate text-ink">{{ $product->name }}</span>
        </nav>

        <div class="mt-8 grid gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:gap-14" x-data="{ active: 0 }">
            <div>
                <div class="overflow-hidden rounded-[2rem] border border-ink/8 bg-parchment">
                    <img :src="@js($gallery->all())[active]" src="{{ $gallery->first() }}" alt="{{ $product->name }}" class="aspect-square w-full object-cover">
                </div>

                @if ($gallery->count() > 1)
                    <div class="mt-3 grid grid-cols-5 gap-3">
                        @foreach ($gallery as $image)
                            <button type="button" @click="active = {{ $loop->index }}" class="overflow-hidden rounded-xl border-2 bg-parchment transition" :class="active === {{ $loop->index }} ? 'border-rose' : 'border-transparent hover:border-ink/20'">
                                <img src="{{ $image }}" alt="" class="aspect-square w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="card-flat p-4">
                        <p class="label">Preparation</p>
                        <p class="text-sm font-semibold">{{ $product->preparation_hours }} hours notice</p>
                    </div>
                    <div class="card-flat p-4">
                        <p class="label">Serves</p>
                        <p class="text-sm font-semibold">{{ $product->servings ?: 'Ask the bakery' }}</p>
                    </div>
                    <div class="card-flat p-4">
                        <p class="label">Availability</p>
                        <p class="text-sm font-semibold {{ $product->inStock() ? 'text-emerald-700' : 'text-rose' }}">{{ $product->inStock() ? 'Available' : 'Sold out' }}</p>
                    </div>
                </div>

                @if ($product->ingredients || $product->allergens)
                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        @if ($product->ingredients)
                            <div class="card-flat p-5">
                                <p class="eyebrow">Ingredients</p>
                                <p class="mt-2 text-sm leading-6 text-cocoa">{{ $product->ingredients }}</p>
                            </div>
                        @endif
                        @if ($product->allergens)
                            <div class="card-flat p-5">
                                <p class="eyebrow">Allergens</p>
                                <p class="mt-2 text-sm leading-6 text-cocoa">{{ $product->allergens }}</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('shops.show', $product->shop) }}" class="badge bg-parchment text-cocoa ring-ink/10">{{ $product->shop->name }}</a>
                    @if ($product->is_featured)
                        <span class="badge bg-blush text-rose ring-rose/15">Featured</span>
                    @endif
                </div>

                <h1 class="mt-4 font-display text-4xl font-semibold leading-tight tracking-tight sm:text-5xl">{{ $product->name }}</h1>

                <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-cocoa">
                    <span class="inline-flex items-center gap-2">
                        @include('partials.stars', ['rating' => $averageRating ?: (float) $product->shop->rating])
                        <span class="font-semibold text-ink">{{ $averageRating > 0 ? number_format($averageRating, 1) : number_format((float) $product->shop->rating, 1) }}</span>
                        <span>({{ $reviews->count() }} reviews)</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="h-4 w-4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-5.4 7-11a7 7 0 1 0-14 0c0 5.6 7 11 7 11Z" /><circle cx="12" cy="10" r="2.5" /></svg>
                        {{ $product->shop->city }}
                    </span>
                </div>

                <p class="mt-6 font-display text-4xl font-semibold">₱{{ number_format((float) $product->base_price, 0) }}</p>
                <p class="mt-1 text-sm text-cocoa">{{ $product->category?->name }} · Customisations may add to the final price.</p>

                <p class="mt-6 whitespace-pre-line text-[0.95rem] leading-7 text-cocoa">{{ $product->description }}</p>

                <form method="POST" action="{{ route('cart.add') }}" class="mt-8 space-y-6" x-data="{ quantity: 1 }">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    @if ($product->variants->isNotEmpty())
                        <div>
                            <p class="label">Choose a size</p>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($product->variants as $variant)
                                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-ink/12 bg-white px-4 py-3 text-sm transition has-[:checked]:border-rose has-[:checked]:bg-blush/35">
                                        <span class="flex items-center gap-2">
                                            <input type="radio" name="variant_id" value="{{ $variant->id }}" @checked($defaultVariant?->id === $variant->id) class="border-ink/20 text-rose focus:ring-rose">
                                            <span class="font-medium">{{ $variant->name }}</span>
                                        </span>
                                        <span class="font-display font-semibold">₱{{ number_format((float) $variant->price, 0) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @foreach ($options as $group)
                        <div>
                            <label for="option-{{ $group['key'] }}" class="label">{{ $group['label'] }}{{ ! empty($group['required']) ? ' *' : '' }}</label>
                            <select name="customizations[{{ $group['key'] }}]" id="option-{{ $group['key'] }}" class="input" @required(! empty($group['required']))>
                                <option value="">Select {{ strtolower($group['label']) }}</option>
                                @foreach ($group['options'] ?? [] as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}{{ (float) ($option['price'] ?? 0) > 0 ? ' · +₱'.number_format((float) $option['price'], 0) : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach

                    <div class="flex flex-wrap items-end gap-4">
                        <div>
                            <p class="label">Quantity</p>
                            <div class="inline-flex items-center rounded-full border border-ink/15 bg-white">
                                <button type="button" @click="quantity = Math.max(1, quantity - 1)" class="grid h-10 w-10 place-items-center rounded-full text-cocoa hover:bg-parchment" aria-label="Decrease quantity">
                                    <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path fill-rule="evenodd" d="M4 10a.75.75 0 0 1 .75-.75h10.5a.75.75 0 0 1 0 1.5H4.75A.75.75 0 0 1 4 10Z" clip-rule="evenodd" /></svg>
                                </button>
                                <input type="number" name="quantity" x-model.number="quantity" min="1" max="20" class="w-12 border-0 bg-transparent p-0 text-center text-sm font-semibold focus:ring-0">
                                <button type="button" @click="quantity = Math.min(20, quantity + 1)" class="grid h-10 w-10 place-items-center rounded-full text-cocoa hover:bg-parchment" aria-label="Increase quantity">
                                    <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>
                                </button>
                            </div>
                        </div>
                        <p class="pb-2 text-xs text-cocoa">Preparation and pickup dates are confirmed at checkout.</p>
                    </div>

                    <div>
                        <label for="special_instructions" class="label">Message or special instructions</label>
                        <textarea name="special_instructions" id="special_instructions" rows="3" maxlength="500" class="input" placeholder="For example: “Happy birthday, Mia” in chocolate script.">{{ old('special_instructions') }}</textarea>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button type="submit" class="btn-primary flex-1" @disabled(! $product->inStock())>Add to basket</button>
                        <button type="submit" name="buy_now" value="1" class="btn-rose flex-1" @disabled(! $product->inStock())>Buy now</button>
                    </div>
                </form>

                <div class="mt-8 rounded-2xl border border-ink/8 bg-parchment/50 p-5">
                    <div class="flex items-start gap-3">
                        <img src="{{ $product->shop->logoUrl() }}" alt="" class="h-11 w-11 rounded-full object-cover ring-1 ring-ink/10">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold">{{ $product->shop->name }}</p>
                            <p class="mt-0.5 text-xs leading-5 text-cocoa">{{ $product->shop->tagline ?: $product->shop->description }}</p>
                            <a href="{{ route('shops.show', $product->shop) }}" class="mt-2 inline-flex text-xs font-semibold text-rose hover:underline">Visit bakery</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section class="mt-20 grid gap-10 lg:grid-cols-[1.4fr_0.6fr]">
            <div>
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-3xl font-semibold">Reviews</h2>
                    <span class="text-sm text-cocoa">{{ $reviews->count() }} customer {{ \Illuminate\Support\Str::plural('review', $reviews->count()) }}</span>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($reviews as $review)
                        <article class="card-flat p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $review->customer?->avatarUrl() }}" alt="" class="h-10 w-10 rounded-full object-cover">
                                    <div>
                                        <p class="text-sm font-semibold">{{ $review->customer?->name ?? 'SweetCrumbs customer' }}</p>
                                        <p class="text-xs text-cocoa">{{ $review->created_at->format('M j, Y') }}</p>
                                    </div>
                                </div>
                                @include('partials.stars', ['rating' => $review->rating])
                            </div>
                            @if ($review->comment)
                                <p class="mt-4 text-sm leading-7 text-cocoa">{{ $review->comment }}</p>
                            @endif
                        </article>
                    @empty
                        <div class="card-flat p-6 text-sm text-cocoa">No reviews yet for this cake. Be the first to celebrate it.</div>
                    @endforelse
                </div>
            </div>

            <aside>
                <div class="rounded-3xl bg-ink p-6 text-cream">
                    <p class="eyebrow text-cream/60">A note on ordering</p>
                    <p class="mt-3 font-display text-2xl font-semibold leading-snug">Every cake is made to order by one bakery.</p>
                    <p class="mt-3 text-sm leading-6 text-cream/70">Your basket can only hold cakes from one bakery at a time. Payment is verified by Xendit before the bakery begins.</p>
                </div>

                <div class="mt-5 card-flat p-5 text-sm leading-6 text-cocoa">
                    <p class="font-semibold text-ink">Allergy note</p>
                    <p class="mt-2">Bakeries may work in kitchens that handle nuts, dairy, eggs, and wheat. Contact the bakery directly for specific allergen guidance.</p>
                </div>
            </aside>
        </section>

        @if ($related->isNotEmpty())
            <section class="mt-20">
                <h2 class="font-display text-3xl font-semibold">More from {{ $product->shop->name }}</h2>
                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($related as $relatedProduct)
                        @include('partials.product-card', ['product' => $relatedProduct])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
