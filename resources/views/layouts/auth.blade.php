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
    <div class="grid min-h-screen lg:grid-cols-[0.95fr_1.05fr]">
        <aside class="relative hidden overflow-hidden bg-ink p-10 text-cream lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -left-24 top-16 h-80 w-80 rounded-full bg-rose/20 blur-3xl"></div>
            <div class="absolute -bottom-24 right-0 h-96 w-96 rounded-full bg-gold/15 blur-3xl"></div>

            <a href="{{ route('home') }}" class="relative flex items-center gap-2.5">
                @include('partials.brand-logo', ['class' => 'h-24 w-auto'])
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
</body>
</html>
