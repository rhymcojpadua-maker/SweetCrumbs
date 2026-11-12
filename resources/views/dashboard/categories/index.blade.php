@extends('layouts.dashboard')

@section('title', 'Categories')
@section('eyebrow', 'Catalogue')
@section('heading', 'Categories')

@section('content')
    <div class="grid gap-5 xl:grid-cols-[1fr_21rem]">
        <div class="card overflow-hidden">
            <div class="border-b border-ink/8 px-5 py-4 sm:px-6">
                <h2 class="font-display text-xl font-semibold">Your categories</h2>
                <p class="mt-1 text-xs text-cocoa">Used to organise the storefront filters and product listings.</p>
            </div>

            <div class="divide-y divide-ink/8">
                @forelse ($categories as $category)
                    <div class="px-5 py-4 sm:px-6" x-data="{ editing: false }">
                        <div class="flex flex-wrap items-center justify-between gap-3" x-show="!editing">
                            <div>
                                <p class="font-semibold">{{ $category->name }}</p>
                                <p class="mt-0.5 text-xs text-cocoa">{{ $category->products_count }} {{ \Illuminate\Support\Str::plural('cake', $category->products_count) }} · sort {{ $category->sort_order }}</p>
                                @if ($category->description)
                                    <p class="mt-1 max-w-lg text-xs text-cocoa">{{ $category->description }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="badge {{ $category->is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-stone-100 text-stone-600 ring-stone-200' }}">{{ $category->is_active ? 'Visible' : 'Hidden' }}</span>
                                <button type="button" class="btn-secondary btn-sm" @click="editing = true">Edit</button>
                                <form method="POST" action="{{ route('dashboard.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-ghost btn-sm text-rose" @disabled($category->products_count > 0)>Delete</button>
                                </form>
                            </div>
                        </div>

                        <form x-show="editing" x-cloak method="POST" action="{{ route('dashboard.categories.update', $category) }}" class="grid gap-3 sm:grid-cols-[1fr_10rem_6rem_auto]">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label class="label text-[10px]">Name</label>
                                <input type="text" name="name" value="{{ $category->name }}" required maxlength="80" class="input py-2 text-sm">
                            </div>
                            <div>
                                <label class="label text-[10px]">Sort order</label>
                                <input type="number" name="sort_order" value="{{ $category->sort_order }}" min="0" max="999" class="input py-2 text-sm">
                            </div>
                            <label class="flex items-end gap-2 pb-2 text-xs text-cocoa">
                                <input type="checkbox" name="is_active" value="1" @checked($category->is_active) class="rounded border-ink/20 text-rose focus:ring-rose">
                                Visible
                            </label>
                            <div class="flex items-end gap-2">
                                <button type="submit" class="btn-primary btn-sm">Save</button>
                                <button type="button" class="btn-ghost btn-sm" @click="editing = false">Cancel</button>
                            </div>
                            <div class="sm:col-span-4">
                                <label class="label text-[10px]">Description</label>
                                <input type="text" name="description" value="{{ $category->description }}" maxlength="500" class="input py-2 text-sm">
                            </div>
                        </form>
                    </div>
                @empty
                    <p class="px-5 py-12 text-center text-sm text-cocoa">No categories yet. Add one on the right to start organising your cakes.</p>
                @endforelse
            </div>
        </div>

        <aside class="card h-fit p-5 sm:p-6">
            <h2 class="font-display text-xl font-semibold">Add a category</h2>
            <form method="POST" action="{{ route('dashboard.categories.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="name" class="label">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="80" class="input" placeholder="Celebration cakes">
                </div>
                <div>
                    <label for="description" class="label">Description (optional)</label>
                    <textarea name="description" id="description" rows="2" maxlength="500" class="input"></textarea>
                </div>
                <div>
                    <label for="sort_order" class="label">Sort order</label>
                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}" min="0" max="999" class="input">
                </div>
                <label class="flex items-center gap-2 text-sm text-cocoa">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-ink/20 text-rose focus:ring-rose">
                    Show on storefront
                </label>
                <button type="submit" class="btn-primary w-full">Create category</button>
            </form>
        </aside>
    </div>
@endsection
