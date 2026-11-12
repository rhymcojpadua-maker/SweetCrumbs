@extends('layouts.admin')

@section('title', 'Activity')
@section('eyebrow', 'Audit trail')
@section('heading', 'Activity')

@section('actions')
    <form method="GET" action="{{ route('admin.activity.index') }}" class="flex flex-wrap items-end gap-2">
        <div class="min-w-44 flex-1 sm:max-w-xs">
            <label for="q" class="sr-only">Search activity</label>
            <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}" class="input py-2 text-sm" placeholder="Search descriptions">
        </div>
        <div>
            <label for="action" class="sr-only">Action</label>
            <select name="action" id="action" class="input py-2 text-sm">
                <option value="">Any action</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(($filters['action'] ?? null) === $action)>{{ $action }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="shop" class="sr-only">Bakery</label>
            <select name="shop" id="shop" class="input py-2 text-sm">
                <option value="">Any bakery</option>
                @foreach ($shops as $shop)
                    <option value="{{ $shop->id }}" @selected((string) ($filters['shop'] ?? '') === (string) $shop->id)>{{ $shop->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-secondary btn-sm">Filter</button>
    </form>
@endsection

@section('content')
    <div class="card overflow-hidden">
        <ol class="divide-y divide-ink/8">
            @forelse ($logs as $log)
                <li class="flex items-start gap-4 px-5 py-4 sm:px-6">
                    <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-full bg-parchment text-cocoa">
                        @include('partials.icon', ['name' => 'activity', 'class' => 'h-4 w-4'])
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-medium">{{ $log->description }}</p>
                            <p class="text-[10px] uppercase tracking-wide text-cocoa/60">{{ $log->created_at->format('M j, Y g:i A') }}</p>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-cocoa">
                            <span class="font-mono">{{ $log->action }}</span>
                            <span>{{ $log->user?->name ?? 'System' }}</span>
                            @if ($log->shop)<span>{{ $log->shop->name }}</span>@endif
                            @if ($log->ip_address)<span>{{ $log->ip_address }}</span>@endif
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-5 py-16 text-center text-sm text-cocoa">No activity matches this view.</li>
            @endforelse
        </ol>
    </div>

    <div class="mt-5">{{ $logs->links() }}</div>
@endsection
