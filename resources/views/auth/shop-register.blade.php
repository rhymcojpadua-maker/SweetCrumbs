@extends('layouts.auth')

@section('title', 'Sell with Cakelle')

@section('content')
    <div>
        <p class="eyebrow">Sell with Cakelle</p>
        <h1 class="mt-3 font-display text-4xl font-semibold tracking-tight">Bring your bakery to the marketplace.</h1>
        <p class="mt-3 text-sm leading-6 text-cocoa">Tell us about your studio. Every application is reviewed by our team before your storefront opens. You can set up cakes and schedules once approved.</p>
    </div>

    <form method="POST" action="{{ route('shop.register.store') }}" class="mt-8 space-y-8">
        @csrf

        <section class="space-y-4">
            <h2 class="font-display text-2xl font-semibold">Your account</h2>
            <div>
                <label for="name" class="label">Your name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required autocomplete="name" class="input">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="email" class="label">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email" class="input">
                </div>
                <div>
                    <label for="phone" class="label">Phone</label>
                    <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" required autocomplete="tel" class="input">
                </div>
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
        </section>

        <section class="space-y-4 border-t border-ink/8 pt-8">
            <h2 class="font-display text-2xl font-semibold">Your bakery</h2>
            <div>
                <label for="shop_name" class="label">Bakery name</label>
                <input type="text" name="shop_name" id="shop_name" value="{{ old('shop_name') }}" required class="input">
            </div>
            <div>
                <label for="tagline" class="label">Tagline (optional)</label>
                <input type="text" name="tagline" id="tagline" value="{{ old('tagline') }}" maxlength="160" class="input" placeholder="A short line that captures your point of view.">
            </div>
            <div>
                <label for="description" class="label">About your bakery</label>
                <textarea name="description" id="description" rows="5" required minlength="40" maxlength="2000" class="input" placeholder="What do you bake? How did you start? What makes your cakes yours?">{{ old('description') }}</textarea>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="shop_email" class="label">Bakery email</label>
                    <input type="email" name="shop_email" id="shop_email" value="{{ old('shop_email') }}" required class="input">
                </div>
                <div>
                    <label for="shop_phone" class="label">Bakery phone</label>
                    <input type="tel" name="shop_phone" id="shop_phone" value="{{ old('shop_phone') }}" required class="input">
                </div>
            </div>
            <div>
                <label for="address" class="label">Street address</label>
                <input type="text" name="address" id="address" value="{{ old('address') }}" required class="input">
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="city" class="label">City</label>
                    <input type="text" name="city" id="city" value="{{ old('city') }}" required class="input">
                </div>
                <div>
                    <label for="province" class="label">Province</label>
                    <input type="text" name="province" id="province" value="{{ old('province') }}" required class="input">
                </div>
                <div>
                    <label for="postal_code" class="label">Postal code</label>
                    <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code') }}" class="input">
                </div>
            </div>
        </section>

        <button type="submit" class="btn-primary w-full">Submit application</button>
        <p class="text-center text-xs leading-5 text-cocoa">You will be signed in and taken to your application status. Our team reviews new bakeries before they go live.</p>
    </form>

    <p class="mt-6 text-center text-sm text-cocoa">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-rose hover:underline">Sign in</a>
    </p>
@endsection
