@extends('layouts.public')

@section('title', 'Your basket — SweetCrumbs')

@section('content')
    <div class="container-page py-12">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">Your basket</p>
                <h1 class="mt-3 font-display text-4xl font-semibold tracking-tight sm:text-5xl">The cakes you have chosen.</h1>
            </div>
            @if ($cart && $cart->items->isNotEmpty())
                <a href="{{ route('marketplace') }}" class="btn-secondary">Keep browsing</a>
            @endif
        </div>

        @if (! $cart || $cart->items->isEmpty())
            <div class="mt-10 card p-10 text-center sm:p-16">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-blush text-rose">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-8 w-8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12l-1 12H7L6 7Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 7a3 3 0 0 1 6 0" /></svg>
                </span>
                <h2 class="mt-5 font-display text-3xl font-semibold">Your basket is waiting.</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-cocoa">Find a cake from one of our independent bakeries, then come back here to choose a collection or delivery date.</p>
                <a href="{{ route('marketplace') }}" class="mt-6 btn-primary">Explore cakes</a>
            </div>
        @else
            <div class="mt-10 grid gap-8 lg:grid-cols-[1fr_22rem]">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4 rounded-2xl border border-ink/8 bg-parchment/40 px-4 py-3 text-sm">
                        <div class="flex items-center gap-3">
                            <img src="{{ $cart->shop->logoUrl() }}" alt="" class="h-8 w-8 rounded-full object-cover">
                            <span>Ordering from <a href="{{ route('shops.show', $cart->shop) }}" class="font-semibold text-ink hover:text-rose">{{ $cart->shop->name }}</a></span>
                        </div>
                        <form method="POST" action="{{ route('cart.clear') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold text-cocoa hover:text-rose">Clear basket</button>
                        </form>
                    </div>

                    @foreach ($cart->items as $item)
                        <article class="card p-4 sm:p-5">
                            <div class="flex gap-4">
                                <a href="{{ route('products.show', $item->product) }}" class="shrink-0 overflow-hidden rounded-2xl bg-parchment">
                                    <img src="{{ $item->product->images->first()?->url() ?? $item->product->imageUrl() }}" alt="{{ $item->product_name ?? $item->product->name }}" class="h-24 w-24 object-cover sm:h-28 sm:w-28">
                                </a>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <h2 class="font-display text-xl font-semibold leading-snug">
                                                <a href="{{ route('products.show', $item->product) }}" class="hover:text-rose">{{ $item->product->name }}</a>
                                            </h2>
                                            <p class="mt-1 text-xs text-cocoa">{{ $item->variant?->name ?: 'Standard size' }} · {{ $item->product->shop->name }}</p>
                                        </div>
                                        <p class="font-display text-lg font-semibold">₱{{ number_format($item->lineTotal(), 0) }}</p>
                                    </div>

                                    @if (! empty($item->customizations))
                                        <ul class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-cocoa">
                                            @foreach ($item->customizations as $customization)
                                                <li>{{ $customization['group'] }}: <span class="font-medium text-ink">{{ $customization['label'] }}</span></li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @if ($item->special_instructions)
                                        <p class="mt-2 rounded-lg bg-parchment/60 px-3 py-2 text-xs leading-5 text-cocoa">“{{ $item->special_instructions }}”</p>
                                    @endif

                                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <form method="POST" action="{{ route('cart.update', $item) }}" class="flex items-center gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <label for="qty-{{ $item->id }}" class="text-xs font-semibold uppercase tracking-[0.1em] text-cocoa">Qty</label>
                                                <input type="number" name="quantity" id="qty-{{ $item->id }}" value="{{ $item->quantity }}" min="0" max="20" class="input w-16 px-2 py-1.5 text-center text-sm">
                                                <button type="submit" class="btn-ghost btn-sm">Update</button>
                                            </form>
                                            <span class="text-xs text-cocoa">₱{{ number_format((float) $item->unit_price, 0) }} each</span>
                                        </div>

                                        <form method="POST" action="{{ route('cart.remove', $item) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-semibold text-cocoa hover:text-rose">Remove</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <aside>
                    <div class="sticky top-28 card p-6">
                        <h2 class="font-display text-2xl font-semibold">Order summary</h2>

                        <dl class="mt-5 space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-cocoa">Items ({{ $cart->itemCount() }})</dt>
                                <dd class="font-semibold">₱{{ number_format($cart->subtotal(), 0) }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-cocoa">Delivery</dt>
                                <dd class="text-right text-xs text-cocoa">Calculated at checkout</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-t border-ink/8 pt-3">
                                <dt class="font-semibold">Estimated total</dt>
                                <dd class="font-display text-2xl font-semibold">₱{{ number_format($cart->subtotal(), 0) }}</dd>
                            </div>
                        </dl>

                        <a href="{{ route('checkout.show') }}" class="mt-6 btn-primary w-full">Continue to checkout</a>

                        <div class="mt-5 space-y-2 text-xs leading-5 text-cocoa">
                            <p class="flex items-start gap-2">
                                <svg viewBox="0 0 20 20" fill="currentColor" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-600" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0l-3.5-3.5a1 1 0 1 1 1.4-1.4l2.8 2.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" /></svg>
                                Secure payment through Xendit
                            </p>
                            <p class="flex items-start gap-2">
                                <svg viewBox="0 0 20 20" fill="currentColor" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-600" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0l-3.5-3.5a1 1 0 1 1 1.4-1.4l2.8 2.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" /></svg>
                                One bakery per order, made fresh
                            </p>
                        </div>
                    </div>
                </aside>
            </div>
        @endif
    </div>
@endsection
