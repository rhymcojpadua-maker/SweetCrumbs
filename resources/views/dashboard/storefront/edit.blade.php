@extends('layouts.dashboard')

@section('title', 'Storefront')
@section('eyebrow', 'Settings')
@section('heading', 'Storefront')

@section('actions')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-cocoa">This is what customers see when they visit your bakery.</p>
        <a href="{{ route('shops.show', $shop) }}" target="_blank" rel="noopener" class="btn-secondary btn-sm">Preview storefront</a>
    </div>
@endsection

@section('content')
    <form method="POST" action="{{ route('dashboard.storefront.update') }}" enctype="multipart/form-data" class="grid gap-5 xl:grid-cols-[1fr_21rem]">
        @csrf
        @method('PATCH')

        <div class="space-y-5">
            <section class="card overflow-hidden">
                <div class="relative h-44 bg-ink">
                    <img src="{{ $shop->coverUrl() }}" alt="" class="h-full w-full object-cover opacity-80">
                    <div class="absolute inset-0 bg-gradient-to-t from-ink/60 to-transparent"></div>
                </div>
                <div class="-mt-9 px-5 pb-5 sm:px-6">
                    <img src="{{ $shop->logoUrl() }}" alt="" class="h-20 w-20 rounded-2xl border-4 border-white object-cover shadow-lg">
                    <p class="mt-3 font-display text-2xl font-semibold">{{ $shop->name }}</p>
                    <p class="text-sm text-cocoa">{{ $shop->city }}, {{ $shop->province }}</p>
                </div>
            </section>

            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-2xl font-semibold">Identity</h2>
                <div class="mt-5 space-y-4">
                    <div>
                        <label for="name" class="label">Bakery name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $shop->name) }}" required maxlength="120" class="input">
                    </div>
                    <div>
                        <label for="tagline" class="label">Tagline</label>
                        <input type="text" name="tagline" id="tagline" value="{{ old('tagline', $shop->tagline) }}" maxlength="160" class="input" placeholder="Small-batch cakes for the moments that matter.">
                    </div>
                    <div>
                        <label for="description" class="label">Your story</label>
                        <textarea name="description" id="description" rows="6" required minlength="40" maxlength="3000" class="input">{{ old('description', $shop->description) }}</textarea>
                    </div>
                </div>
            </section>

            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-2xl font-semibold">Contact &amp; location</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="email" class="label">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $shop->email) }}" required class="input">
                    </div>
                    <div>
                        <label for="phone" class="label">Phone</label>
                        <input type="tel" name="phone" id="phone" value="{{ old('phone', $shop->phone) }}" required class="input">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="address" class="label">Street address</label>
                        <input type="text" name="address" id="address" value="{{ old('address', $shop->address) }}" required class="input">
                    </div>
                    <div>
                        <label for="city" class="label">City</label>
                        <input type="text" name="city" id="city" value="{{ old('city', $shop->city) }}" required class="input">
                    </div>
                    <div>
                        <label for="province" class="label">Province</label>
                        <input type="text" name="province" id="province" value="{{ old('province', $shop->province) }}" required class="input">
                    </div>
                    <div>
                        <label for="postal_code" class="label">Postal code</label>
                        <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $shop->postal_code) }}" class="input">
                    </div>
                </div>
            </section>
        </div>

        <aside class="space-y-5">
            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-xl font-semibold">Photography</h2>
                <div class="mt-4 space-y-4">
                    <div>
                        <label for="logo" class="label">Logo</label>
                        <input type="file" name="logo" id="logo" accept="image/jpeg,image/png,image/webp" class="input py-2 text-sm">
                        <p class="mt-1 text-xs text-cocoa">Square works best.</p>
                    </div>
                    <div>
                        <label for="cover" class="label">Cover image</label>
                        <input type="file" name="cover" id="cover" accept="image/jpeg,image/png,image/webp" class="input py-2 text-sm">
                        <p class="mt-1 text-xs text-cocoa">Wide landscape, at least 1600px.</p>
                    </div>
                </div>
                <button type="submit" class="mt-5 btn-primary w-full">Save storefront</button>
            </section>

            <section class="card-flat p-5 text-sm leading-6 text-cocoa">
                <p class="font-semibold text-ink">Your public address</p>
                <p class="mt-1 font-mono text-xs break-all">{{ route('shops.show', $shop) }}</p>
                <p class="mt-2">Your bakery slug stays stable even if you change the display name.</p>
            </section>
        </aside>
    </form>
@endsection
