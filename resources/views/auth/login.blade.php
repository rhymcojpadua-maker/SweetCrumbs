@extends('layouts.auth')

@section('title', 'Sign in — SweetCrumbs')

@section('content')
    <div>
        <p class="eyebrow">Welcome back</p>
        <h1 class="mt-3 font-display text-4xl font-semibold tracking-tight">Sign in to SweetCrumbs.</h1>
        <p class="mt-3 text-sm text-cocoa">Pick up a basket, check an order, or open your bakery dashboard.</p>
    </div>

    <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-4">
        @csrf
        <div>
            <label for="email" class="label">Email address</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="input">
        </div>
        <div>
            <label for="password" class="label">Password</label>
            <input type="password" name="password" id="password" required autocomplete="current-password" class="input">
        </div>
        <label class="flex items-center gap-2 text-sm text-cocoa">
            <input type="checkbox" name="remember" value="1" class="rounded border-ink/20 text-rose focus:ring-rose">
            Keep me signed in
        </label>

        <button type="submit" class="btn-primary w-full">Sign in</button>
    </form>

    <p class="mt-6 text-center text-sm text-cocoa">
        New to SweetCrumbs?
        <a href="{{ route('register') }}" class="font-semibold text-rose hover:underline">Create an account</a>
    </p>
    <p class="mt-2 text-center text-sm text-cocoa">
        Baking for a living?
        <a href="{{ route('shop.register') }}" class="font-semibold text-rose hover:underline">Apply as a bakery</a>
    </p>
@endsection
