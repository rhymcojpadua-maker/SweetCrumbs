<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Cakelle — Cakes with a story')</title>
    <meta name="description" content="@yield('description', 'Discover independent Filipino bakeries, design your perfect cake, and reserve a pickup or delivery with Cakelle.')">

    <link rel="icon" href="{{ asset('favicon.svg') }}?v={{ filemtime(public_path('favicon.svg')) }}" type="image/svg+xml">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-cream font-sans text-ink">
    <header x-data="{ mobileOpen: false, accountOpen: false }" class="sticky top-0 z-40 border-b border-ink/8 bg-cream/85 backdrop-blur-xl">
        <div class="container-page">
            <div class="flex h-[4.5rem] items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-ink font-display text-lg font-semibold text-cream">C</span>
                    <span class="font-display text-2xl font-semibold tracking-tight">Cakelle</span>
                </a>

                <nav class="hidden items-center gap-7 lg:flex">
                    <a href="{{ route('marketplace') }}" class="nav-link {{ request()->routeIs('marketplace', 'products.show') ? 'nav-link-active' : '' }}">Cakes</a>
                    <a href="{{ route('shops.index') }}" class="nav-link {{ request()->routeIs('shops.*') ? 'nav-link-active' : '' }}">Bakeries</a>
                    <a href="{{ route('about') }}" class="nav-link {{ request()->routeIs('about') ? 'nav-link-active' : '' }}">Our story</a>
                    <a href="{{ route('faqs') }}" class="nav-link {{ request()->routeIs('faqs') ? 'nav-link-active' : '' }}">FAQs</a>
                    <a href="{{ route('orders.track.form') }}" class="nav-link {{ request()->routeIs('orders.track.*') ? 'nav-link-active' : '' }}">Track order</a>
                </nav>

                <div class="flex items-center gap-2">
                    <a href="{{ route('cart.index') }}" class="relative inline-flex h-10 w-10 items-center justify-center rounded-full text-cocoa transition hover:bg-parchment hover:text-ink" aria-label="Basket">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="h-5 w-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12l-1 12H7L6 7Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 7a3 3 0 0 1 6 0" />
                        </svg>
                        @if ($navCartCount > 0)
                            <span class="absolute -right-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-rose px-1 text-[0.65rem] font-bold text-white">{{ $navCartCount }}</span>
                        @endif
                    </a>

                    @auth
                        <div class="relative hidden sm:block">
                            <button type="button" @click="accountOpen = !accountOpen" class="flex items-center gap-2 rounded-full border border-ink/10 bg-white/70 py-1.5 pl-1.5 pr-3 text-sm font-semibold transition hover:border-ink/20">
                                <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="h-7 w-7 rounded-full object-cover">
                                <span class="max-w-24 truncate">{{ auth()->user()->name }}</span>
                                <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-cocoa" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-cloak x-show="accountOpen" @click.outside="accountOpen = false" class="absolute right-0 mt-2 w-56 overflow-hidden rounded-2xl border border-ink/10 bg-white shadow-xl">
                                <div class="border-b border-ink/8 px-4 py-3">
                                    <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                                    <p class="truncate text-xs text-cocoa">{{ auth()->user()->email }}</p>
                                </div>
                                <div class="p-2 text-sm">
                                    @if (auth()->user()->isSuperAdmin())
                                        <a href="{{ route('admin.dashboard') }}" class="block rounded-lg px-3 py-2 font-medium text-cocoa hover:bg-parchment hover:text-ink">Admin dashboard</a>
                                    @elseif (auth()->user()->isShopOwner())
                                        <a href="{{ route('dashboard.index') }}" class="block rounded-lg px-3 py-2 font-medium text-cocoa hover:bg-parchment hover:text-ink">Bakery dashboard</a>
                                    @endif
                                    <a href="{{ route('orders.track.form') }}" class="block rounded-lg px-3 py-2 font-medium text-cocoa hover:bg-parchment hover:text-ink">Track an order</a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left font-medium text-rose hover:bg-blush/60">Sign out</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="hidden sm:inline-flex btn-secondary btn-sm">Sign in</a>
                        <a href="{{ route('register') }}" class="hidden md:inline-flex btn-primary btn-sm">Join Cakelle</a>
                    @endauth

                    <button type="button" @click="mobileOpen = !mobileOpen" class="inline-flex h-10 w-10 items-center justify-center rounded-full text-cocoa hover:bg-parchment lg:hidden" aria-label="Menu">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5" aria-hidden="true">
                            <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <div x-cloak x-show="mobileOpen" class="border-t border-ink/8 py-4 lg:hidden">
                <nav class="grid gap-1 text-sm font-medium">
                    <a href="{{ route('marketplace') }}" class="rounded-xl px-3 py-2.5 hover:bg-parchment">Cakes</a>
                    <a href="{{ route('shops.index') }}" class="rounded-xl px-3 py-2.5 hover:bg-parchment">Bakeries</a>
                    <a href="{{ route('about') }}" class="rounded-xl px-3 py-2.5 hover:bg-parchment">Our story</a>
                    <a href="{{ route('faqs') }}" class="rounded-xl px-3 py-2.5 hover:bg-parchment">FAQs</a>
                    <a href="{{ route('orders.track.form') }}" class="rounded-xl px-3 py-2.5 hover:bg-parchment">Track order</a>
                    @guest
                        <div class="mt-3 flex gap-2 px-1">
                            <a href="{{ route('login') }}" class="btn-secondary btn-sm flex-1">Sign in</a>
                            <a href="{{ route('register') }}" class="btn-primary btn-sm flex-1">Join Cakelle</a>
                        </div>
                    @endguest
                </nav>
            </div>
        </div>
    </header>

    @include('partials.flash')

    <main>
        @yield('content')
    </main>

    <footer class="mt-24 border-t border-ink/8 bg-parchment/40">
        <div class="container-page py-14">
            <div class="grid gap-10 md:grid-cols-[1.4fr_1fr_1fr_1fr]">
                <div>
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                        <span class="grid h-9 w-9 place-items-center rounded-full bg-ink font-display text-lg font-semibold text-cream">C</span>
                        <span class="font-display text-2xl font-semibold tracking-tight">Cakelle</span>
                    </a>
                    <p class="mt-4 max-w-sm text-sm leading-6 text-cocoa">A considered marketplace for the Philippines' independent bakers — and the celebrations that bring us together.</p>
                    <p class="mt-5 text-xs text-cocoa/70">Made with care in the Philippines.</p>
                </div>

                <div>
                    <p class="eyebrow">Discover</p>
                    <ul class="mt-4 space-y-2.5 text-sm text-cocoa">
                        <li><a href="{{ route('marketplace') }}" class="hover:text-ink">All cakes</a></li>
                        <li><a href="{{ route('shops.index') }}" class="hover:text-ink">Bakeries</a></li>
                        <li><a href="{{ route('faqs') }}" class="hover:text-ink">FAQs</a></li>
                        <li><a href="{{ route('orders.track.form') }}" class="hover:text-ink">Track an order</a></li>
                    </ul>
                </div>

                <div>
                    <p class="eyebrow">For bakers</p>
                    <ul class="mt-4 space-y-2.5 text-sm text-cocoa">
                        <li><a href="{{ route('shop.register') }}" class="hover:text-ink">Sell with Cakelle</a></li>
                        @auth
                            @if (auth()->user()->isShopOwner())
                                <li><a href="{{ route('dashboard.index') }}" class="hover:text-ink">Bakery dashboard</a></li>
                            @endif
                        @endauth
                        <li><a href="{{ route('terms') }}" class="hover:text-ink">Seller terms</a></li>
                    </ul>
                </div>

                <div>
                    <p class="eyebrow">Company</p>
                    <ul class="mt-4 space-y-2.5 text-sm text-cocoa">
                        <li><a href="{{ route('about') }}" class="hover:text-ink">Our story</a></li>
                        <li><a href="{{ route('privacy') }}" class="hover:text-ink">Privacy</a></li>
                        <li><a href="mailto:{{ config('cakelle.support_email') }}" class="hover:text-ink">{{ config('cakelle.support_email') }}</a></li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 flex flex-col gap-3 border-t border-ink/8 pt-6 text-xs text-cocoa/70 sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ now()->year }} Cakelle. All rights reserved.</p>
                <p>Payments verified securely through PayMongo.</p>
            </div>
        </div>
    </footer>
</body>
</html>
