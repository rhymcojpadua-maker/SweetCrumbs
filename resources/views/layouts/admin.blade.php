@php
    $adminNav = [
        ['label' => 'Overview', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'dashboard'],
        ['label' => 'Applications', 'route' => 'admin.applications.index', 'active' => 'admin.applications.*', 'icon' => 'shop'],
        ['label' => 'Bakeries', 'route' => 'admin.shops.index', 'active' => 'admin.shops.*', 'icon' => 'storefront'],
        ['label' => 'People', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'icon' => 'customers'],
        ['label' => 'Orders', 'route' => 'admin.orders.index', 'active' => 'admin.orders.*', 'icon' => 'orders'],
        ['label' => 'Payments', 'route' => 'admin.payments.index', 'active' => 'admin.payments.*', 'icon' => 'payments'],
        ['label' => 'Activity', 'route' => 'admin.activity.index', 'active' => 'admin.activity.*', 'icon' => 'activity'],
    ];
    $adminSettings = [
        ['label' => 'Platform settings', 'route' => 'admin.settings.edit', 'active' => 'admin.settings.*', 'icon' => 'settings'],
        ['label' => 'Website content', 'route' => 'admin.content.index', 'active' => 'admin.content.*', 'icon' => 'content'],
    ];
    $pendingApplications = \App\Models\Shop::query()->where('status', \App\Enums\ShopStatus::Pending->value)->count();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Administration') — Cakelle</title>

    <link rel="icon" href="{{ asset('favicon.svg') }}?v={{ filemtime(public_path('favicon.svg')) }}" type="image/svg+xml">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-[#f5f1ea] font-sans text-ink" x-data="{ sidebar: false, account: false }" @keydown.escape.window="sidebar = false; account = false">
    <div x-cloak x-show="sidebar" x-transition.opacity class="fixed inset-0 z-40 bg-ink/40 backdrop-blur-sm lg:hidden" @click="sidebar = false"></div>

    <aside class="fixed inset-y-0 left-0 z-50 flex w-[17rem] -translate-x-full flex-col bg-ink text-cream transition-transform duration-200 lg:translate-x-0" :class="sidebar ? 'translate-x-0' : '-translate-x-full'">
        <div class="flex h-16 items-center justify-between border-b border-white/10 px-5">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                <span class="grid h-8 w-8 place-items-center rounded-full bg-cream font-display text-base font-semibold text-ink">C</span>
                <span class="font-display text-xl font-semibold tracking-tight">Cakelle</span>
                <span class="rounded-full bg-white/10 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-cream/70">Admin</span>
            </a>
            <button type="button" class="grid h-8 w-8 place-items-center rounded-full text-cream/70 hover:bg-white/10 lg:hidden" @click="sidebar = false" aria-label="Close menu">
                @include('partials.icon', ['name' => 'close', 'class' => 'h-4 w-4'])
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-5">
            <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-cream/40">Marketplace</p>
            <ul class="space-y-0.5">
                @foreach ($adminNav as $item)
                    <li>
                        <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs($item['active']) ? 'bg-cream text-ink' : 'text-cream/70 hover:bg-white/10 hover:text-cream' }}">
                            @include('partials.icon', ['name' => $item['icon'], 'class' => 'h-[18px] w-[18px]'])
                            <span>{{ $item['label'] }}</span>
                            @if ($item['route'] === 'admin.applications.index' && $pendingApplications > 0)
                                <span class="ml-auto rounded-full bg-rose px-2 py-0.5 text-[10px] font-bold text-white">{{ $pendingApplications }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>

            <p class="px-3 pb-2 pt-6 text-[10px] font-semibold uppercase tracking-[0.18em] text-cream/40">Configuration</p>
            <ul class="space-y-0.5">
                @foreach ($adminSettings as $item)
                    <li>
                        <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs($item['active']) ? 'bg-cream text-ink' : 'text-cream/70 hover:bg-white/10 hover:text-cream' }}">
                            @include('partials.icon', ['name' => $item['icon'], 'class' => 'h-[18px] w-[18px]'])
                            <span>{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="border-t border-white/10 p-3">
            <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-cream/70 transition hover:bg-white/10 hover:text-cream">
                @include('partials.icon', ['name' => 'home', 'class' => 'h-[18px] w-[18px]'])
                <span>Back to marketplace</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-cream/70 transition hover:bg-white/10 hover:text-cream">
                    @include('partials.icon', ['name' => 'logout', 'class' => 'h-[18px] w-[18px]'])
                    <span>Sign out</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="lg:pl-[17rem]">
        <header class="sticky top-0 z-30 border-b border-ink/8 bg-[#f5f1ea]/90 backdrop-blur">
            <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                <button type="button" class="grid h-9 w-9 place-items-center rounded-full text-cocoa hover:bg-ink/5 lg:hidden" @click="sidebar = true" aria-label="Open menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" /></svg>
                </button>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-[11px] font-semibold uppercase tracking-[0.16em] text-cocoa/60">@yield('eyebrow', 'Marketplace administration')</p>
                    <h1 class="truncate font-display text-lg font-semibold leading-tight">@yield('heading', 'Overview')</h1>
                </div>

                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2 hover:bg-ink/5" @click="open = !open">
                        <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="h-8 w-8 rounded-full object-cover">
                        <span class="hidden text-sm font-semibold sm:block">{{ auth()->user()->name }}</span>
                        <svg viewBox="0 0 20 20" fill="currentColor" class="hidden h-4 w-4 text-cocoa sm:block"><path fill-rule="evenodd" d="M5.2 7.4a1 1 0 0 1 1.4 0L10 10.8l3.4-3.4a1 1 0 1 1 1.4 1.4l-4.1 4.1a1 1 0 0 1-1.4 0L5.2 8.8a1 1 0 0 1 0-1.4Z" clip-rule="evenodd" /></svg>
                    </button>
                    <div x-cloak x-show="open" x-transition.origin.top.right class="absolute right-0 mt-2 w-60 overflow-hidden rounded-2xl border border-ink/10 bg-white p-1.5 shadow-xl shadow-ink/10">
                        <div class="border-b border-ink/8 px-3 py-2.5">
                            <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-cocoa">{{ auth()->user()->email }}</p>
                            <p class="mt-1 text-[10px] font-bold uppercase tracking-wider text-rose">{{ auth()->user()->role->label() }}</p>
                        </div>
                        <a href="{{ url('/cp') }}" target="_blank" rel="noopener" class="mt-1 flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-parchment/60">
                            @include('partials.icon', ['name' => 'content', 'class' => 'h-4 w-4 text-cocoa'])
                            Open Statamic
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-rose hover:bg-rose/5">
                                @include('partials.icon', ['name' => 'logout', 'class' => 'h-4 w-4'])
                                Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            @hasSection('actions')
                <div class="border-t border-ink/8 px-4 py-3 sm:px-6 lg:px-8">
                    @yield('actions')
                </div>
            @endif
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
