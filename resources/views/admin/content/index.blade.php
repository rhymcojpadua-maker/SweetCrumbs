@extends('layouts.admin')

@section('title', 'Website content')
@section('eyebrow', 'Content')
@section('heading', 'Website content')

@section('actions')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-cocoa">Marketing pages, FAQs, and testimonials are managed in Statamic.</p>
        <a href="{{ $cmsUrl }}" target="_blank" rel="noopener" class="btn-primary btn-sm">Open Statamic control panel</a>
    </div>
@endsection

@section('content')
    <div class="grid gap-5 xl:grid-cols-2">
        <section class="card overflow-hidden">
            <div class="border-b border-ink/8 px-5 py-4 sm:px-6">
                <h2 class="font-display text-xl font-semibold">Pages</h2>
                <p class="mt-1 text-xs text-cocoa">{{ $pages->count() }} published pages</p>
            </div>
            <ul class="divide-y divide-ink/8">
                @forelse ($pages as $page)
                    <li class="flex items-center justify-between gap-3 px-5 py-3.5 sm:px-6">
                        <div>
                            <p class="text-sm font-semibold">{{ $page->get('title') ?: $page->slug() }}</p>
                            <p class="font-mono text-xs text-cocoa">{{ $page->url() }}</p>
                        </div>
                        <a href="{{ $page->url() }}" target="_blank" rel="noopener" class="btn-ghost btn-sm">View</a>
                    </li>
                @empty
                    <li class="px-5 py-12 text-center text-sm text-cocoa">No published pages yet.</li>
                @endforelse
            </ul>
        </section>

        <section class="card overflow-hidden">
            <div class="border-b border-ink/8 px-5 py-4 sm:px-6">
                <h2 class="font-display text-xl font-semibold">FAQs</h2>
                <p class="mt-1 text-xs text-cocoa">{{ $faqs->count() }} published questions</p>
            </div>
            <ol class="divide-y divide-ink/8">
                @forelse ($faqs as $faq)
                    <li class="px-5 py-3.5 sm:px-6">
                        <p class="text-sm font-semibold">{{ $faq['question'] }}</p>
                        <p class="mt-1 line-clamp-2 text-xs leading-5 text-cocoa">{{ \Illuminate\Support\Str::limit(strip_tags($faq['answer']), 150) }}</p>
                    </li>
                @empty
                    <li class="px-5 py-12 text-center text-sm text-cocoa">No published FAQs yet.</li>
                @endforelse
            </ol>
        </section>

        <section class="card overflow-hidden xl:col-span-2">
            <div class="border-b border-ink/8 px-5 py-4 sm:px-6">
                <h2 class="font-display text-xl font-semibold">Testimonials</h2>
                <p class="mt-1 text-xs text-cocoa">{{ $testimonials->count() }} published quotes</p>
            </div>
            <div class="grid gap-px bg-ink/8 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($testimonials as $testimonial)
                    <figure class="bg-white p-5">
                        <div class="flex items-center gap-1 text-gold">
                            @for ($i = 1; $i <= 5; $i++)
                                <svg viewBox="0 0 24 24" fill="currentColor" class="h-3.5 w-3.5 {{ $i <= $testimonial['rating'] ? '' : 'text-ink/15' }}"><path d="M12 3.3l2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8-4.3-4.1 5.9-.9L12 3.3Z" /></svg>
                            @endfor
                        </div>
                        <blockquote class="mt-3 text-sm leading-6 text-cocoa">“{{ $testimonial['quote'] }}”</blockquote>
                        <figcaption class="mt-3 text-xs font-semibold">{{ $testimonial['name'] }} <span class="font-normal text-cocoa">· {{ $testimonial['detail'] }}</span></figcaption>
                    </figure>
                @empty
                    <p class="col-span-full bg-white px-5 py-12 text-center text-sm text-cocoa">No published testimonials yet.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="mt-5 rounded-2xl bg-ink p-5 text-cream sm:p-6">
        <p class="font-display text-xl font-semibold">Editing content</p>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-cream/65">Use the Statamic control panel to update page copy, FAQs, testimonials, and the homepage global. Changes appear on the public site immediately after saving and publishing.</p>
    </div>
@endsection
