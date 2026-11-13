@php
    $dashboardShop = $shop ?? auth()->user()?->shop;
    $nav = [
        ['label' => 'Overview', 'route' => 'dashboard.index', 'active' => 'dashboard.index', 'icon' => 'dashboard'],
        ['label' => 'Orders', 'route' => 'dashboard.orders.index', 'active' => 'dashboard.orders.*', 'icon' => 'orders'],
        ['label' => 'Cakes', 'route' => 'dashboard.products.index', 'active' => 'dashboard.products.*', 'icon' => 'products'],
        ['label' => 'Categories', 'route' => 'dashboard.categories.index', 'active' => 'dashboard.categories.*', 'icon' => 'categories'],
        ['label' => 'Inventory', 'route' => 'dashboard.inventory.index', 'active' => 'dashboard.inventory.*', 'icon' => 'inventory'],
        ['label' => 'Customers', 'route' => 'dashboard.customers.index', 'active' => 'dashboard.customers.*', 'icon' => 'customers'],
        ['label' => 'Payments', 'route' => 'dashboard.payments.index', 'active' => 'dashboard.payments.*', 'icon' => 'payments'],
        ['label' => 'Reports', 'route' => 'dashboard.reports.index', 'active' => 'dashboard.reports.*', 'icon' => 'reports'],
    ];
    $settingsNav = [
        ['label' => 'Storefront', 'route' => 'dashboard.storefront.edit', 'active' => 'dashboard.storefront.*', 'icon' => 'storefront'],
        ['label' => 'Fulfillment', 'route' => 'dashboard.fulfillment.edit', 'active' => 'dashboard.fulfillment.*', 'icon' => 'calendar'],
        ['label' => 'Notifications', 'route' => 'dashboard.notifications.index', 'active' => 'dashboard.notifications.*', 'icon' => 'bell'],
        ['label' => 'Your profile', 'route' => 'dashboard.profile.edit', 'active' => 'dashboard.profile.*', 'icon' => 'user'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') — {{ $dashboardShop?->name ?? 'SweetCrumbs' }}</title>

    <link rel="icon" href="{{ asset('favicon.png') }}?v={{ filemtime(public_path('favicon.png')) }}" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-[#f5f1ea] font-sans text-ink" x-data="{ sidebar: false, account: false, notifications: false }" @keydown.escape.window="sidebar = false; account = false; notifications = false">
    <div x-cloak x-show="sidebar" x-transition.opacity class="fixed inset-0 z-40 bg-ink/40 backdrop-blur-sm lg:hidden" @click="sidebar = false"></div>

    <aside class="fixed inset-y-0 left-0 z-50 flex w-[17.5rem] -translate-x-full flex-col border-r border-ink/8 bg-parchment transition-transform duration-200 lg:translate-x-0" :class="sidebar ? 'translate-x-0' : '-translate-x-full'">
        <div class="flex h-16 items-center justify-between border-b border-ink/8 px-5">
            <a href="{{ route('dashboard.index') }}" class="flex items-center gap-2.5">
                @include('partials.brand-logo', ['class' => 'h-12 w-auto'])
            </a>
            <button type="button" class="grid h-8 w-8 place-items-center rounded-full text-cocoa hover:bg-ink/5 lg:hidden" @click="sidebar = false" aria-label="Close menu">
                @include('partials.icon', ['name' => 'close', 'class' => 'h-4 w-4'])
            </button>
        </div>

        <div class="border-b border-ink/8 px-4 py-4">
            <div class="flex items-center gap-3 rounded-xl bg-white/60 p-2.5">
                <img src="{{ $dashboardShop?->logoUrl() ?? 'https://ui-avatars.com/api/?name=SweetCrumbs&background=2b211b&color=fbf7f1' }}" alt="" class="h-9 w-9 rounded-full object-cover">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold">{{ $dashboardShop?->name ?? 'Your bakery' }}</p>
                    <p class="truncate text-xs text-cocoa">{{ $dashboardShop?->city ?? 'Setup needed' }}</p>
                </div>
            </div>
            @if ($dashboardShop)
                <a href="{{ route('shops.show', $dashboardShop) }}" target="_blank" rel="noopener" class="mt-2.5 inline-flex items-center gap-1.5 px-1 text-xs font-semibold text-cocoa hover:text-rose">
                    View storefront
                    @include('partials.icon', ['name' => 'external', 'class' => 'h-3.5 w-3.5'])
                </a>
            @endif
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4">
            <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-cocoa/60">Manage</p>
            <ul class="space-y-0.5">
                @foreach ($nav as $item)
                    <li>
                        <a href="{{ route($item['route']) }}" class="sidebar-link {{ request()->routeIs($item['active']) ? 'sidebar-link-active' : '' }}">
                            @include('partials.icon', ['name' => $item['icon'], 'class' => 'h-[18px] w-[18px]'])
                            <span>{{ $item['label'] }}</span>
                            @if ($item['route'] === 'dashboard.orders.index')
                                @php($pending = \App\Models\Order::query()->where('shop_id', $dashboardShop?->id)->where('status', \App\Enums\OrderStatus::PendingConfirmation->value)->count())
                                @if ($pending > 0)
                                    <span class="ml-auto rounded-full bg-rose/15 px-2 py-0.5 text-[10px] font-bold text-rose">{{ $pending }}</span>
                                @endif
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>

            <p class="px-3 pb-2 pt-6 text-[10px] font-semibold uppercase tracking-[0.18em] text-cocoa/60">Settings</p>
            <ul class="space-y-0.5">
                @foreach ($settingsNav as $item)
                    <li>
                        <a href="{{ route($item['route']) }}" class="sidebar-link {{ request()->routeIs($item['active']) ? 'sidebar-link-active' : '' }}">
                            @include('partials.icon', ['name' => $item['icon'], 'class' => 'h-[18px] w-[18px]'])
                            <span>{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="border-t border-ink/8 p-3">
            <a href="{{ route('home') }}" class="sidebar-link">
                @include('partials.icon', ['name' => 'home', 'class' => 'h-[18px] w-[18px]'])
                <span>Back to marketplace</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sidebar-link w-full text-left">
                    @include('partials.icon', ['name' => 'logout', 'class' => 'h-[18px] w-[18px]'])
                    <span>Sign out</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="lg:pl-[17.5rem]">
        <header class="sticky top-0 z-30 border-b border-ink/8 bg-[#f5f1ea]/90 backdrop-blur">
            <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                <button type="button" class="grid h-9 w-9 place-items-center rounded-full text-cocoa hover:bg-ink/5 lg:hidden" @click="sidebar = true" aria-label="Open menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" /></svg>
                </button>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-[11px] font-semibold uppercase tracking-[0.16em] text-cocoa/60">@yield('eyebrow', 'Bakery dashboard')</p>
                    <h1 class="truncate font-display text-lg font-semibold leading-tight">@yield('heading', 'Dashboard')</h1>
                </div>

                <div class="flex items-center gap-1.5">
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button" class="relative grid h-9 w-9 place-items-center rounded-full text-cocoa hover:bg-ink/5" @click="open = !open" aria-label="Notifications">
                            @include('partials.icon', ['name' => 'bell', 'class' => 'h-5 w-5'])
                            @if ($unreadNotificationCount > 0)
                                <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-rose ring-2 ring-[#f5f1ea]"></span>
                            @endif
                        </button>
                        <div x-cloak x-show="open" x-transition.origin.top.right class="absolute right-0 mt-2 w-80 overflow-hidden rounded-2xl border border-ink/10 bg-white shadow-xl shadow-ink/10">
                            <div class="flex items-center justify-between border-b border-ink/8 px-4 py-3">
                                <p class="text-sm font-semibold">Notifications</p>
                                @if ($unreadNotificationCount > 0)
                                    <form method="POST" action="{{ route('dashboard.notifications.read-all') }}">
                                        @csrf
                                        <button class="text-xs font-semibold text-rose hover:underline">Mark all read</button>
                                    </form>
                                @endif
                            </div>
                            <div class="max-h-80 divide-y divide-ink/5 overflow-y-auto">
                                @forelse ($unreadNotifications as $notification)
                                    <form method="POST" action="{{ route('dashboard.notifications.read', $notification) }}">
                                        @csrf
                                        <button type="submit" class="block w-full px-4 py-3 text-left hover:bg-parchment/50">
                                            <p class="text-sm font-medium leading-5">{{ $notification->title }}</p>
                                            <p class="mt-0.5 text-xs text-cocoa">{{ $notification->body }}</p>
                                            <p class="mt-1 text-[10px] uppercase tracking-wide text-cocoa/60">{{ $notification->created_at->diffForHumans() }}</p>
                                        </button>
                                    </form>
                                @empty
                                    <p class="px-4 py-8 text-center text-sm text-cocoa">You are all caught up.</p>
                                @endforelse
                            </div>
                            <a href="{{ route('dashboard.notifications.index') }}" class="block border-t border-ink/8 px-4 py-3 text-center text-xs font-semibold text-cocoa hover:bg-parchment/50">View all notifications</a>
                        </div>
                    </div>

                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button" class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2 hover:bg-ink/5" @click="open = !open">
                            <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="h-8 w-8 rounded-full object-cover">
                            <span class="hidden text-sm font-semibold sm:block">{{ auth()->user()->name }}</span>
                            <svg viewBox="0 0 20 20" fill="currentColor" class="hidden h-4 w-4 text-cocoa sm:block"><path fill-rule="evenodd" d="M5.2 7.4a1 1 0 0 1 1.4 0L10 10.8l3.4-3.4a1 1 0 1 1 1.4 1.4l-4.1 4.1a1 1 0 0 1-1.4 0L5.2 8.8a1 1 0 0 1 0-1.4Z" clip-rule="evenodd" /></svg>
                        </button>
                        <div x-cloak x-show="open" x-transition.origin.top.right class="absolute right-0 mt-2 w-56 overflow-hidden rounded-2xl border border-ink/10 bg-white p-1.5 shadow-xl shadow-ink/10">
                            <div class="border-b border-ink/8 px-3 py-2.5">
                                <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-cocoa">{{ auth()->user()->email }}</p>
                            </div>
                            <a href="{{ route('dashboard.profile.edit') }}" class="mt-1 flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-parchment/60">
                                @include('partials.icon', ['name' => 'user', 'class' => 'h-4 w-4 text-cocoa'])
                                Profile
                            </a>
                            <a href="{{ route('dashboard.storefront.edit') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-parchment/60">
                                @include('partials.icon', ['name' => 'storefront', 'class' => 'h-4 w-4 text-cocoa'])
                                Storefront
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
