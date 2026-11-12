@php($image = $product->images->first()?->url() ?? $product->imageUrl())
<article class="group overflow-hidden rounded-3xl border border-ink/8 bg-white shadow-[0_18px_50px_-30px_rgba(43,33,27,0.35)] transition hover:-translate-y-0.5 hover:shadow-[0_26px_60px_-30px_rgba(43,33,27,0.4)]">
    <a href="{{ route('products.show', $product) }}" class="block">
        <div class="relative aspect-[4/3] overflow-hidden bg-parchment">
            <img src="{{ $image }}" alt="{{ $product->name }}" loading="lazy" class="h-full w-full object-cover transition duration-700 group-hover:scale-[1.04]">
            @if ($product->is_featured)
                <span class="absolute left-3 top-3 badge bg-cream/95 text-cocoa ring-ink/10">Featured</span>
            @endif
            @if (! $product->inStock())
                <span class="absolute right-3 top-3 badge bg-ink/85 text-cream ring-white/20">Sold out</span>
            @endif
        </div>
    </a>

    <div class="p-4.5">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('shops.show', $product->shop) }}" class="truncate text-xs font-semibold uppercase tracking-[0.12em] text-cocoa hover:text-rose">{{ $product->shop->name }}</a>
            <span class="shrink-0 text-xs text-cocoa/70">{{ $product->category?->name }}</span>
        </div>

        <h3 class="mt-2 font-display text-xl font-semibold leading-snug">
            <a href="{{ route('products.show', $product) }}" class="hover:text-rose">{{ $product->name }}</a>
        </h3>

        <p class="mt-1.5 line-clamp-2 text-sm leading-6 text-cocoa">{{ $product->description }}</p>

        <div class="mt-4 flex items-end justify-between gap-3">
            <div>
                <p class="text-[0.68rem] font-semibold uppercase tracking-[0.12em] text-cocoa/60">From</p>
                <p class="font-display text-xl font-semibold">₱{{ number_format((float) $product->base_price, 0) }}</p>
            </div>
            <a href="{{ route('products.show', $product) }}" class="btn-secondary btn-sm">View cake</a>
        </div>
    </div>
</article>
