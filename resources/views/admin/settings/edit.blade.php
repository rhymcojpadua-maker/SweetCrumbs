@extends('layouts.admin')

@section('title', 'Platform settings')
@section('eyebrow', 'Configuration')
@section('heading', 'Platform settings')

@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}" class="grid gap-5 xl:grid-cols-[1fr_21rem]">
        @csrf
        @method('PATCH')

        <div class="space-y-5">
            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-2xl font-semibold">Marketplace identity</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="platform_name" class="label">Platform name</label>
                        <input type="text" name="platform_name" id="platform_name" value="{{ old('platform_name', $settings['platform_name']) }}" required maxlength="80" class="input">
                    </div>
                    <div>
                        <label for="support_email" class="label">Support email</label>
                        <input type="email" name="support_email" id="support_email" value="{{ old('support_email', $settings['support_email']) }}" required class="input">
                    </div>
                    <div>
                        <label for="support_phone" class="label">Support phone</label>
                        <input type="tel" name="support_phone" id="support_phone" value="{{ old('support_phone', $settings['support_phone']) }}" required class="input">
                    </div>
                </div>
            </section>

            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-2xl font-semibold">Announcement banner</h2>
                <div class="mt-5 space-y-4">
                    <div>
                        <label for="announcement" class="label">Message</label>
                        <textarea name="announcement" id="announcement" rows="3" maxlength="300" class="input" placeholder="A short note shown across the public marketplace.">{{ old('announcement', $settings['announcement']) }}</textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-cocoa">
                        <input type="checkbox" name="announcement_enabled" value="1" @checked(old('announcement_enabled', $settings['announcement_enabled'])) class="rounded border-ink/20 text-rose focus:ring-rose">
                        Show the announcement banner
                    </label>
                </div>
            </section>
        </div>

        <aside class="space-y-5">
            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-xl font-semibold">Save settings</h2>
                <p class="mt-2 text-sm leading-6 text-cocoa">These values are stored in the platform settings table and apply immediately.</p>
                <button type="submit" class="mt-4 btn-primary w-full">Save settings</button>
            </section>

            <section class="card-flat p-5 text-sm leading-6 text-cocoa">
                <p class="font-semibold text-ink">Payment configuration</p>
                <p class="mt-1">Xendit credentials live in the environment file, not here. Set <span class="font-mono text-xs">XENDIT_SECRET_KEY</span> and <span class="font-mono text-xs">XENDIT_WEBHOOK_TOKEN</span> to enable checkout and verify payment updates.</p>
            </section>
        </aside>
    </form>
@endsection
