@php($name = $name ?? 'dot')
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $stroke ?? 1.7 }}" class="{{ $class ?? 'h-5 w-5' }}" aria-hidden="true">
    @switch($name)
        @case('dashboard')
            <rect x="3.5" y="3.5" width="7" height="7" rx="1.5" /><rect x="13.5" y="3.5" width="7" height="7" rx="1.5" /><rect x="3.5" y="13.5" width="7" height="7" rx="1.5" /><rect x="13.5" y="13.5" width="7" height="7" rx="1.5" />
            @break
        @case('orders')
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.5h10a1.5 1.5 0 0 1 1.5 1.5v15l-3-2-3.5 2-3.5-2-3 2V5A1.5 1.5 0 0 1 7 3.5Z" /><path stroke-linecap="round" d="M9 8h6M9 12h6" />
            @break
        @case('products')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.5 20 8v8l-8 4.5L4 16V8l8-4.5Z" /><path stroke-linecap="round" stroke-linejoin="round" d="m4.3 8 7.7 4.4L19.7 8M12 20.5v-8.1" />
            @break
        @case('categories')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5h6v6H4zM14 5.5h6v6h-6zM4 15.5h6v3H4zM14 15.5h6v3h-6z" />
            @break
        @case('inventory')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z" /><path stroke-linecap="round" stroke-linejoin="round" d="m8 9.7 8-4.4M8 14.5V9.7M16 14.5V9.7" />
            @break
        @case('customers')
            <circle cx="9" cy="8" r="3.2" /><path stroke-linecap="round" d="M3.5 19.5c0-3 2.5-5.2 5.5-5.2s5.5 2.2 5.5 5.2" /><path stroke-linecap="round" d="M16 5.5a3 3 0 0 1 0 5.8M17.5 14.5c1.8.5 3 2.2 3 4.2" />
            @break
        @case('payments')
            <rect x="3" y="5.5" width="18" height="13" rx="2.5" /><path stroke-linecap="round" d="M3 10h18M7 15h3" />
            @break
        @case('reports')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5V5M4 19.5h16M8 16v-4M12.5 16V8M17 16v-6" />
            @break
        @case('storefront')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 9.5V19a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9.5M3.5 9.5 5.5 4h13l2 5.5" /><path stroke-linecap="round" d="M3.5 9.5c0 1.4 1.1 2.5 2.5 2.5s2.5-1.1 2.5-2.5c0 1.4 1.1 2.5 2.5 2.5s2.5-1.1 2.5-2.5c0 1.4 1.1 2.5 2.5 2.5s2.5-1.1 2.5-2.5c0 1.4 1.1 2.5 2.5 2.5" />
            @break
        @case('calendar')
            <rect x="3.5" y="5" width="17" height="15" rx="2.5" /><path stroke-linecap="round" d="M8 3v4M16 3v4M3.5 10h17" />
            @break
        @case('bell')
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9.5a6 6 0 0 1 12 0c0 4 1.5 5.5 1.5 5.5h-15S6 13.5 6 9.5Z" /><path stroke-linecap="round" d="M10 18.5a2.2 2.2 0 0 0 4 0" />
            @break
        @case('user')
            <circle cx="12" cy="8" r="3.5" /><path stroke-linecap="round" d="M5 20c0-3.3 3.1-5.8 7-5.8s7 2.5 7 5.8" />
            @break
        @case('settings')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.2a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06-2.2 2.2-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1 1.55V20h-3.1v-.08a1.7 1.7 0 0 0-1.1-1.55 1.7 1.7 0 0 0-1.87.34l-.06.06-2.2-2.2.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.55-1H3v-3.1h.08a1.7 1.7 0 0 0 1.55-1.1 1.7 1.7 0 0 0-.34-1.87l-.06-.06 2.2-2.2.06.06A1.7 1.7 0 0 0 8.36 6a1.7 1.7 0 0 0 1-1.55V4.4h3.1v.08a1.7 1.7 0 0 0 1 1.55 1.7 1.7 0 0 0 1.87-.34l.06-.06 2.2 2.2-.06.06a1.7 1.7 0 0 0-.34 1.87 1.7 1.7 0 0 0 1.55 1H21v3.1h-.08a1.7 1.7 0 0 0-1.52 1Z" />
            @break
        @case('home')
            <path stroke-linecap="round" stroke-linejoin="round" d="m3.5 10.5 8.5-7 8.5 7M5.5 9.5v10h13v-10M9.8 19.5v-5.2h4.4v5.2" />
            @break
        @case('shop')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 9.5V19a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9.5M3.5 9.5 5.5 4h13l2 5.5M9 20v-5h6v5" />
            @break
        @case('search')
            <circle cx="10.5" cy="10.5" r="6" /><path stroke-linecap="round" d="m15 15 4.5 4.5" />
            @break
        @case('plus')
            <path stroke-linecap="round" d="M12 5v14M5 12h14" />
            @break
        @case('check')
            <path stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.5 4.5L19 7" />
            @break
        @case('close')
            <path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" />
            @break
        @case('edit')
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 16.5-.8 3.8 3.8-.8L19 8a2.3 2.3 0 0 0-3.2-3.2L4.5 16.5Z" /><path stroke-linecap="round" d="m13.8 6.2 4 4" />
            @break
        @case('trash')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V4.5h6V7M6.5 7l.8 13h9.4l.8-13M10 11v5M14 11v5" />
            @break
        @case('eye')
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.8 12S6 6.5 12 6.5 21.2 12 21.2 12 18 17.5 12 17.5 2.8 12 2.8 12Z" /><circle cx="12" cy="12" r="2.7" />
            @break
        @case('external')
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 4h6v6M20 4l-9 9M11 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5" />
            @break
        @case('logout')
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-2M10 12h10M17 9l3 3-3 3" />
            @break
        @case('activity')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h4l2-6 4.5 12 2.5-6h5" />
            @break
        @case('content')
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z" /><path stroke-linecap="round" d="M8 8h8M8 12h8M8 16h5" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="8.5" /><path stroke-linecap="round" d="M12 7.5V12l3 2" />
            @break
        @case('truck')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6.5h10v10H3zM13 10h4l3 3v3.5h-7z" /><circle cx="7" cy="18" r="1.5" /><circle cx="17" cy="18" r="1.5" />
            @break
        @case('box')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5 12 12l8-4.5M12 12v9" />
            @break
        @case('money')
            <circle cx="12" cy="12" r="8.5" /><path stroke-linecap="round" stroke-linejoin="round" d="M8.5 9h7M8.5 15h7M12 6.5v11" />
            @break
        @case('chart')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5M4 19h16M8 15v-3M12.5 15V9M17 15v-5" />
            @break
        @case('star')
            <path d="M12 3.3l2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8-4.3-4.1 5.9-.9L12 3.3Z" />
            @break
        @default
            <circle cx="12" cy="12" r="3" />
    @endswitch
</svg>
