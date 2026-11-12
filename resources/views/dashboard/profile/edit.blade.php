@extends('layouts.dashboard')

@section('title', 'Your profile')
@section('eyebrow', 'Settings')
@section('heading', 'Your profile')

@section('content')
    <div class="grid gap-5 xl:grid-cols-[1fr_20rem]">
        <form method="POST" action="{{ route('dashboard.profile.update') }}" class="space-y-5">
            @csrf
            @method('PATCH')

            <section class="card p-5 sm:p-6">
                <div class="flex items-center gap-4">
                    <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="h-14 w-14 rounded-full object-cover">
                    <div>
                        <h2 class="font-display text-2xl font-semibold">{{ auth()->user()->name }}</h2>
                        <p class="text-sm text-cocoa">{{ auth()->user()->role->label() }} · {{ $shop?->name }}</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="label">Full name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', auth()->user()->name) }}" required maxlength="120" class="input">
                    </div>
                    <div>
                        <label for="email" class="label">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email', auth()->user()->email) }}" required class="input">
                    </div>
                    <div>
                        <label for="phone" class="label">Phone</label>
                        <input type="tel" name="phone" id="phone" value="{{ old('phone', auth()->user()->phone) }}" required class="input">
                    </div>
                </div>
            </section>

            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-2xl font-semibold">Change password</h2>
                <p class="mt-1 text-xs text-cocoa">Leave these blank if you do not want to change your password.</p>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="current_password" class="label">Current password</label>
                        <input type="password" name="current_password" id="current_password" autocomplete="current-password" class="input">
                    </div>
                    <div>
                        <label for="password" class="label">New password</label>
                        <input type="password" name="password" id="password" autocomplete="new-password" class="input">
                    </div>
                    <div>
                        <label for="password_confirmation" class="label">Confirm new password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password" class="input">
                    </div>
                </div>
                <button type="submit" class="mt-5 btn-primary">Save profile</button>
            </section>
        </form>

        <aside class="space-y-5">
            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-xl font-semibold">Bakery account</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="label">Bakery</dt><dd class="font-medium">{{ $shop?->name }}</dd></div>
                    <div><dt class="label">Status</dt><dd>@if ($shop)<span class="badge {{ $shop->status->badgeClasses() }}">{{ $shop->status->label() }}</span>@endif</dd></div>
                    <div><dt class="label">Member since</dt><dd class="font-medium">{{ auth()->user()->created_at->format('F Y') }}</dd></div>
                </dl>
                <a href="{{ route('dashboard.storefront.edit') }}" class="mt-4 btn-secondary btn-sm w-full">Edit storefront</a>
            </section>

            <section class="card-flat p-5 text-sm leading-6 text-cocoa">
                <p class="font-semibold text-ink">Account safety</p>
                <p class="mt-1">Use a unique password. If you suspect someone else has access, change it here and sign out of other devices.</p>
            </section>
        </aside>
    </div>
@endsection
