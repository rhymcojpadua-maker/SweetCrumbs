@extends('layouts.dashboard')

@section('title', 'Notifications')
@section('eyebrow', 'Inbox')
@section('heading', 'Notifications')

@section('actions')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-cocoa">{{ $notifications->total() }} {{ \Illuminate\Support\Str::plural('notification', $notifications->total()) }}</p>
        @if ($unreadNotificationCount > 0)
            <form method="POST" action="{{ route('dashboard.notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn-secondary btn-sm">Mark all as read</button>
            </form>
        @endif
    </div>
@endsection

@section('content')
    <div class="card overflow-hidden">
        <ul class="divide-y divide-ink/8">
            @forelse ($notifications as $notification)
                <li class="{{ $notification->read_at ? '' : 'bg-blush/20' }}">
                    <form method="POST" action="{{ route('dashboard.notifications.read', $notification) }}">
                        @csrf
                        <button type="submit" class="flex w-full items-start gap-4 px-5 py-4 text-left transition hover:bg-parchment/40 sm:px-6">
                            <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-full {{ $notification->read_at ? 'bg-parchment text-cocoa' : 'bg-rose/10 text-rose' }}">
                                @include('partials.icon', ['name' => 'bell', 'class' => 'h-4 w-4'])
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-semibold">{{ $notification->title }}</span>
                                    <span class="text-[10px] uppercase tracking-wide text-cocoa/60">{{ $notification->created_at->diffForHumans() }}</span>
                                </span>
                                <span class="mt-1 block text-sm leading-6 text-cocoa">{{ $notification->body }}</span>
                                @if (! $notification->read_at)
                                    <span class="mt-2 inline-flex text-[10px] font-bold uppercase tracking-wider text-rose">Unread</span>
                                @endif
                            </span>
                        </button>
                    </form>
                </li>
            @empty
                <li class="px-5 py-16 text-center">
                    <p class="font-display text-2xl font-semibold">Nothing here yet.</p>
                    <p class="mt-1 text-sm text-cocoa">Order updates and marketplace notices will appear here.</p>
                </li>
            @endforelse
        </ul>
    </div>

    <div class="mt-5">{{ $notifications->links() }}</div>
@endsection
