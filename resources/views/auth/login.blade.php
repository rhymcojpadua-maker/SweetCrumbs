@extends('layouts.auth')

@section('title', 'Sign in — SweetCrumbs')

@section('content')
    <div>
        <p class="text-[0.65rem] font-medium uppercase tracking-[0.18em] text-cocoa">Welcome back</p>
        <h1 class="mt-2 font-display text-2xl font-semibold leading-tight tracking-tight">Sign in to SweetCrumbs.</h1>
        <p class="mt-1.5 text-xs leading-5 text-ink/85">Pick up a basket, check an order, or open your bakery dashboard.</p>
    </div>

    <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label for="email" class="mb-1.5 block text-[0.65rem] font-medium uppercase tracking-[0.13em] text-ink">Email address</label>
            <div class="relative">
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-cocoa/55">
                    <path d="M3.5 5.5h13v9h-13v-9Z" stroke="currentColor" stroke-width="1.2"/>
                    <path d="m4 6 6 4.5L16 6" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                </svg>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="block w-full rounded-lg border border-ink/70 bg-white px-10 py-2 text-sm text-ink shadow-[0_3px_8px_rgba(43,33,27,0.12)] outline-none transition focus:border-rose focus:ring-2 focus:ring-rose/20">
            </div>
        </div>
        <div>
            <div class="mb-1.5 flex items-center justify-between gap-3">
                <label for="password" class="block text-[0.65rem] font-medium uppercase tracking-[0.13em] text-ink">Password</label>
                <a href="mailto:hello@cakelle.ph?subject=Password%20reset%20request" class="text-[0.65rem] font-medium text-ink underline underline-offset-2 hover:text-rose">Forgot password?</a>
            </div>
            <div x-data="{ visible: false }" class="relative">
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-cocoa/55">
                    <rect x="4" y="8.5" width="12" height="8" rx="1.5" stroke="currentColor" stroke-width="1.2"/>
                    <path d="M6.5 8.5V6.8a3.5 3.5 0 0 1 7 0v1.7" stroke="currentColor" stroke-width="1.2"/>
                    <circle cx="10" cy="12.5" r=".8" fill="currentColor"/>
                </svg>
                <input x-bind:type="visible ? 'text' : 'password'" name="password" id="password" required autocomplete="current-password" class="block w-full rounded-lg border border-ink/70 bg-white px-10 py-2 text-sm text-ink shadow-[0_3px_8px_rgba(43,33,27,0.12)] outline-none transition focus:border-rose focus:ring-2 focus:ring-rose/20">
                <button type="button" x-on:click="visible = !visible" x-bind:aria-label="visible ? 'Hide password' : 'Show password'" class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded p-1 text-cocoa transition hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-rose">
                    <svg x-show="!visible" viewBox="0 0 20 20" fill="none" aria-hidden="true" class="size-4">
                        <path d="M2.5 10s2.7-4.5 7.5-4.5 7.5 4.5 7.5 4.5-2.7 4.5-7.5 4.5S2.5 10 2.5 10Z" stroke="currentColor" stroke-width="1.3"/>
                        <circle cx="10" cy="10" r="2" stroke="currentColor" stroke-width="1.3"/>
                    </svg>
                    <svg x-show="visible" x-cloak viewBox="0 0 20 20" fill="none" aria-hidden="true" class="size-4">
                        <path d="m3 3 14 14M8.6 5.7c.5-.1.9-.2 1.4-.2 4.8 0 7.5 4.5 7.5 4.5a12 12 0 0 1-2.4 2.8M5.2 5.9A12.6 12.6 0 0 0 2.5 10s2.7 4.5 7.5 4.5c1 0 1.9-.2 2.7-.6M8.6 8.6a2 2 0 0 0 2.8 2.8" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.3"/>
                    </svg>
                </button>
            </div>
        </div>
        <label class="flex items-center gap-2 text-xs text-ink">
            <input type="checkbox" name="remember" value="1" class="size-3.5 rounded border-ink/35 text-ink focus:ring-rose">
            Keep me signed in
        </label>

        <button type="submit" class="mt-1 inline-flex w-full items-center justify-center rounded-full bg-[#1b100b] px-5 py-2 text-xs font-semibold text-white shadow-[0_4px_10px_rgba(43,33,27,0.2)] transition hover:bg-cocoa focus:outline-none focus-visible:ring-2 focus-visible:ring-rose focus-visible:ring-offset-2">Sign in</button>
    </form>

    <p class="mt-4 text-center text-xs text-cocoa">
        New to SweetCrumbs?
        <a href="{{ route('register') }}" class="font-semibold text-ink hover:text-rose">Create an account</a>
    </p>
    <p class="mt-2 text-center text-xs text-cocoa">
        Baking for a living?
        <a href="{{ route('shop.register') }}" class="font-semibold text-ink hover:text-rose">Apply as a bakery</a>
    </p>
@endsection
