@extends('layouts.admin')

@section('title', 'People')
@section('eyebrow', 'Accounts')
@section('heading', 'People')

@section('actions')
    <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-end gap-2">
        <div class="min-w-44 flex-1 sm:max-w-xs">
            <label for="q" class="sr-only">Search people</label>
            <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}" class="input py-2 text-sm" placeholder="Name, email, or phone">
        </div>
        <div>
            <label for="role" class="sr-only">Role</label>
            <select name="role" id="role" class="input py-2 text-sm">
                <option value="">Any role</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected(($filters['role'] ?? null) === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status" class="sr-only">Account status</label>
            <select name="status" id="status" class="input py-2 text-sm">
                <option value="">Any status</option>
                <option value="active" @selected(($filters['status'] ?? null) === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>Deactivated</option>
            </select>
        </div>
        <button type="submit" class="btn-secondary btn-sm">Filter</button>
    </form>
@endsection

@section('content')
    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Person</th>
                        <th>Role</th>
                        <th>Bakery</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <img src="{{ $user->avatarUrl() }}" alt="" class="h-9 w-9 rounded-full object-cover">
                                    <div>
                                        <p class="font-semibold">{{ $user->name }}</p>
                                        <p class="text-xs text-cocoa">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-parchment text-cocoa ring-ink/10">{{ $user->role->label() }}</span></td>
                            <td class="text-sm">{{ $user->shop?->name ?? '—' }}</td>
                            <td class="text-sm text-cocoa">{{ $user->created_at->format('M j, Y') }}</td>
                            <td><span class="badge {{ $user->is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-stone-100 text-stone-600 ring-stone-200' }}">{{ $user->is_active ? 'Active' : 'Deactivated' }}</span></td>
                            <td class="text-right">
                                @if ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.status', $user) }}" onsubmit="return confirm('{{ $user->is_active ? 'Deactivate' : 'Activate' }} this account?');">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="{{ $user->is_active ? 'deactivate' : 'activate' }}">
                                        <button type="submit" class="btn-ghost btn-sm {{ $user->is_active ? 'text-rose' : '' }}">{{ $user->is_active ? 'Deactivate' : 'Activate' }}</button>
                                    </form>
                                @else
                                    <span class="text-xs text-cocoa">You</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-14 text-center text-sm text-cocoa">No accounts match this view.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $users->links() }}</div>
@endsection
