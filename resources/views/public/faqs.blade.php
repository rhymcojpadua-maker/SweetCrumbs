@extends('layouts.public')

@section('title', ($page?->get('title') ?? 'Frequently asked questions').' — Cakelle')

@section('content')
    <section class="border-b border-ink/8 bg-parchment/35">
        <div class="container-page py-14 lg:py-20">
            <p class="eyebrow">Good to know</p>
            <h1 class="mt-4 max-w-3xl font-display text-4xl font-semibold leading-tight tracking-tight sm:text-5xl">{{ $page?->get('title') ?? 'Frequently asked questions' }}</h1>
            <p class="mt-5 max-w-2xl text-lg leading-8 text-cocoa">{{ $page?->get('intro') ?? 'Everything you need to know about ordering, delivery, and baking with Cakelle.' }}</p>
        </div>
    </section>

    <section class="container-page grid gap-12 py-14 lg:grid-cols-[1fr_20rem]">
        <div class="space-y-3">
            @forelse ($faqs as $faq)
                <details class="group card p-5" {{ $loop->first ? 'open' : '' }}>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">
                        {{ $faq['question'] }}
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full border border-ink/10 text-cocoa transition group-open:rotate-45">
                            <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>
                        </span>
                    </summary>
                    <p class="mt-3 text-sm leading-7 text-cocoa">{{ $faq['answer'] }}</p>
                </details>
            @empty
                <div class="card p-8 text-sm text-cocoa">Our FAQs are being updated. Please write to us in the meantime.</div>
            @endforelse
        </div>

        <aside class="space-y-5">
            <div class="rounded-3xl bg-ink p-6 text-cream">
                <p class="eyebrow text-cream/60">Still curious?</p>
                <p class="mt-3 font-display text-2xl font-semibold leading-snug">Write to a person, not a queue.</p>
                <p class="mt-3 text-sm leading-6 text-cream/70">We usually reply within one working day.</p>
                <a href="mailto:{{ config('cakelle.support_email') }}" class="mt-4 inline-flex text-sm font-semibold text-cream underline decoration-cream/30 underline-offset-4 hover:decoration-cream">{{ config('cakelle.support_email') }}</a>
            </div>

            <div class="card-flat p-5 text-sm leading-6 text-cocoa">
                <p class="font-semibold text-ink">Ordering for a specific date?</p>
                <p class="mt-2">Each cake lists its preparation time. Checkout only shows dates the bakery can actually fulfil.</p>
                <a href="{{ route('marketplace') }}" class="mt-4 btn-secondary btn-sm">Find a cake</a>
            </div>
        </aside>
    </section>
@endsection
