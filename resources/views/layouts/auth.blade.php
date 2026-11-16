<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'SweetCrumbs')</title>

    <link rel="icon" href="{{ asset('favicon.png') }}?v={{ filemtime(public_path('favicon.png')) }}" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-cream font-sans text-ink">
    @if (request()->routeIs('login', 'register'))
        <div class="min-h-screen lg:grid lg:grid-cols-[0.95fr_1.05fr]">
            <aside class="relative hidden min-h-screen flex-col overflow-hidden bg-[#27190e] px-6 py-7 text-cream lg:flex xl:px-12">
                <div class="pointer-events-none absolute -left-28 top-24 h-80 w-80 rounded-full bg-rose/10 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-32 right-0 h-96 w-96 rounded-full bg-gold/10 blur-3xl"></div>

                <a href="{{ route('home') }}" class="relative inline-flex w-fit items-center" aria-label="SweetCrumbs home">
                    @include('partials.brand-logo', ['class' => 'h-auto w-[clamp(15rem,22vw,19rem)]', 'variant' => 'dark'])
                </a>

                <div class="relative flex flex-1 flex-col justify-center py-8">
                    <div class="max-w-lg">
                        <p class="text-[0.65rem] font-medium uppercase tracking-[0.2em] text-cream/55">Cakes with a story</p>
                        <h2 class="mt-5 font-display text-3xl font-semibold leading-[1.12] xl:text-[2.65rem]">
                            Every <span class="italic text-rose">celebration</span> deserves a cake made by <span class="italic text-[#d9c09d]">someone who cares.</span>
                        </h2>
                        <p class="mt-4 max-w-md text-xs leading-6 text-cream/75 sm:text-sm">
                            Join customers and independent bakers across the Philippines in a marketplace built around craft, clarity, and the moment itself.
                        </p>
                    </div>

                    <div class="auth-cake-scene relative mt-7 w-[min(100%,17rem)]">
                        <div class="auth-cake-arch">
                            <img src="https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=900&q=85" alt="A chocolate celebration cake" class="auth-cake-image">
                        </div>
                        <div class="absolute -bottom-4 left-1/2 w-[min(92%,18rem)] -translate-x-1/2 rounded-xl border border-[#d9c09d]/50 bg-[#48382d]/95 px-3 py-2 text-center text-[0.65rem] leading-4 text-cream shadow-lg">
                            <span class="mr-1 text-[#d9c09d]" aria-hidden="true">★</span>
                            Trusted by independent bakers across the Philippines · Handcrafted in the Philippines
                        </div>
                    </div>
                </div>
            </aside>

            <main class="flex min-h-screen flex-col">
                <header class="flex items-center justify-between px-5 py-4 sm:px-8 lg:justify-end">
                    <a href="{{ route('home') }}" class="inline-flex items-center lg:hidden" aria-label="SweetCrumbs home">
                        @include('partials.brand-logo', ['class' => 'h-auto w-40'])
                    </a>
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-lg border border-ink/15 bg-cream/70 px-3 py-1.5 text-xs font-medium text-ink transition hover:bg-white">
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true" class="size-3.5">
                            <path d="M10 4 4 10l6 6M4 10h12" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/>
                        </svg>
                        Back to homepage
                    </a>
                </header>

                <div class="flex flex-1 justify-center px-6 pb-12 pt-5 sm:px-10 lg:items-center lg:pb-16 lg:pt-0">
                    <div class="w-full {{ request()->routeIs('register') ? 'max-w-lg' : 'max-w-[18rem]' }}">
                        @include('partials.flash')
                        @yield('content')
                    </div>
                </div>
            </main>
        </div>
    @else
        <div class="grid min-h-screen lg:grid-cols-[0.95fr_1.05fr]">
            <aside class="relative hidden overflow-hidden bg-ink p-10 text-cream lg:flex lg:flex-col lg:justify-between">
                <div class="absolute -left-24 top-16 h-80 w-80 rounded-full bg-rose/20 blur-3xl"></div>
                <div class="absolute -bottom-24 right-0 h-96 w-96 rounded-full bg-gold/15 blur-3xl"></div>

                <a href="{{ route('home') }}" class="relative flex items-center gap-2.5">
                    @include('partials.brand-logo', ['class' => 'h-24 w-auto', 'variant' => 'dark'])
                </a>

                <div class="relative max-w-md">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cream/50">Cakes with a story</p>
                    <p class="mt-5 font-display text-4xl font-semibold leading-tight">Every celebration deserves a cake made by someone who cares.</p>
                    <p class="mt-5 text-sm leading-7 text-cream/65">Join customers and independent bakers across the Philippines in a marketplace built around craft, clarity, and the moment itself.</p>

                    <div class="mt-8 overflow-hidden rounded-2xl border border-white/10">
                        <img src="https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=1000&q=80" alt="A finished celebration cake" class="aspect-[4/3] w-full object-cover opacity-90">
                    </div>
                </div>

                <p class="relative text-xs text-cream/45">Payments verified through Xendit · Made in the Philippines</p>
            </aside>

            <main class="flex flex-col">
                <div class="flex items-center justify-between border-b border-ink/8 px-5 py-5 sm:px-8 lg:justify-end">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 lg:hidden">
                        @include('partials.brand-logo', ['class' => 'h-12 w-auto'])
                    </a>
                    <a href="{{ route('home') }}" class="text-sm font-semibold text-cocoa hover:text-ink">Back to SweetCrumbs</a>
                </div>

                <div class="flex flex-1 justify-center px-5 py-10 sm:px-8">
                    <div class="w-full max-w-lg">
                        @include('partials.flash')
                        @yield('content')
                    </div>
                </div>
            </main>
        </div>
    @endif
</body>
</html>
