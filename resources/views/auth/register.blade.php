@extends('layouts.auth')

@section('title', 'Create an account — SweetCrumbs')

@section('content')
    <div>
        <p class="eyebrow">Join SweetCrumbs</p>
        <h1 class="mt-3 font-display text-4xl font-semibold tracking-tight">Create your customer account.</h1>
        <p class="mt-3 text-sm text-cocoa">Save your details, keep track of orders, and get back to the cakes.</p>
    </div>

    <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-4">
        @csrf
        <div>
            <label for="name" class="label">Full name</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="input">
        </div>
        <div>
            <label for="email" class="label">Email address</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email" class="input">
        </div>
        <div>
            <label for="phone" class="label">Phone</label>
            <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" required autocomplete="tel" class="input" placeholder="09xx xxx xxxx">
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="password" class="label">Password</label>
                <input type="password" name="password" id="password" required autocomplete="new-password" class="input">
            </div>
            <div>
                <label for="password_confirmation" class="label">Confirm password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password" class="input">
            </div>
        </div>
        <p class="text-xs leading-5 text-cocoa">At least 8 characters, with letters and numbers. By joining you agree to our <a href="{{ route('terms') }}" class="font-semibold text-rose hover:underline">terms</a> and <a href="{{ route('privacy') }}" class="font-semibold text-rose hover:underline">privacy policy</a>.</p>

        <button type="submit" class="btn-primary w-full">Create account</button>
    </form>

    <p class="mt-6 text-center text-sm text-cocoa">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-rose hover:underline">Sign in</a>
    </p>
    <p class="mt-2 text-center text-sm text-cocoa">
        Want to sell your cakes?
        <a href="{{ route('shop.register') }}" class="font-semibold text-rose hover:underline">Apply as a bakery</a>
    </p>
@endsection
