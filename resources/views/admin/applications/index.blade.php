@extends('layouts.admin')

@section('title', 'Applications')
@section('eyebrow', 'Bakery review')
@section('heading', 'Applications')

@section('content')
    <div class="mb-5 rounded-2xl bg-ink p-5 text-cream sm:p-6">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cream/50">Review queue</p>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
            <p class="font-display text-3xl font-semibold">{{ $shops->total() }} {{ \Illuminate\Support\Str::plural('bakery', $shops->total()) }} waiting</p>
            <p class="max-w-md text-sm leading-6 text-cream/65">Read the story, check the location, and talk to the owner if anything is unclear. Approval opens the storefront immediately.</p>
        </div>
    </div>

    <div class="space-y-4">
        @forelse ($shops as $shop)
            <article class="card p-5 sm:p-6" x-data="{ rejecting: false }">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex min-w-0 items-start gap-4">
                        <img src="{{ $shop->logoUrl() }}" alt="" class="h-14 w-14 rounded-2xl object-cover">
                        <div class="min-w-0">
                            <h2 class="font-display text-2xl font-semibold">{{ $shop->name }}</h2>
                            <p class="text-sm text-cocoa">{{ $shop->city }}, {{ $shop->province }} · applied {{ $shop->created_at->format('M j, Y') }}</p>
                            <p class="mt-1 text-xs text-cocoa">{{ $shop->owner?->name }} · {{ $shop->owner?->email }} · {{ $shop->owner?->phone }}</p>
                        </div>
                    </div>
                    <span class="badge {{ $shop->status->badgeClasses() }}">{{ $shop->status->label() }}</span>
                </div>

                <p class="mt-4 max-w-3xl text-sm leading-7 text-cocoa">{{ $shop->description }}</p>

                <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                    <span class="text-cocoa">Email: <span class="font-medium text-ink">{{ $shop->email }}</span></span>
                    <span class="text-cocoa">Phone: <span class="font-medium text-ink">{{ $shop->phone }}</span></span>
                    <span class="text-cocoa">Address: <span class="font-medium text-ink">{{ $shop->address }}</span></span>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-ink/8 pt-5">
                    <form method="POST" action="{{ route('admin.shops.approve', $shop) }}" onsubmit="return confirm('Approve this bakery and open the storefront?');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-primary btn-sm">Approve bakery</button>
                    </form>
                    <button type="button" class="btn-secondary btn-sm" @click="rejecting = !rejecting">Request changes / reject</button>
                    <a href="{{ route('admin.shops.show', $shop) }}" class="btn-ghost btn-sm">Full detail</a>
                </div>

                <form x-cloak x-show="rejecting" method="POST" action="{{ route('admin.shops.reject', $shop) }}" class="mt-4 rounded-xl border border-rose/20 bg-rose/5 p-4">
                    @csrf
                    @method('PATCH')
                    <label for="reason-{{ $shop->id }}" class="label">Reason (sent to the owner)</label>
                    <textarea name="reason" id="reason-{{ $shop->id }}" rows="3" required minlength="10" maxlength="1000" class="input" placeholder="Explain what needs to change before we can approve this bakery."></textarea>
                    <div class="mt-3 flex items-center gap-2">
                        <button type="submit" class="btn-danger btn-sm">Reject application</button>
                        <button type="button" class="btn-ghost btn-sm" @click="rejecting = false">Cancel</button>
                    </div>
                </form>
            </article>
        @empty
            <div class="card px-6 py-16 text-center">
                <p class="font-display text-3xl font-semibold">The queue is clear.</p>
                <p class="mt-2 text-sm text-cocoa">New bakery applications will appear here for review.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-5">{{ $shops->links() }}</div>
@endsection
