@extends('layouts.public')

@section('title', $page->get('title').' — SweetCrumbs')
@section('description', \Illuminate\Support\Str::limit(strip_tags($page->get('intro') ?? ''), 155))

@section('content')
    @php($body = \Illuminate\Support\Str::markdown((string) $page->get('content')))

    <section class="border-b border-ink/8 bg-parchment/35">
        <div class="container-page py-14 lg:py-20">
            <p class="eyebrow">SweetCrumbs</p>
            <h1 class="mt-4 max-w-4xl font-display text-4xl font-semibold leading-tight tracking-tight sm:text-5xl">{{ $page->get('title') }}</h1>
            @if ($page->get('intro'))
                <p class="mt-5 max-w-2xl text-lg leading-8 text-cocoa">{{ $page->get('intro') }}</p>
            @endif
        </div>
    </section>

    <section class="container-page grid gap-12 py-14 lg:grid-cols-[1fr_20rem]">
        <article class="prose-cakelle max-w-3xl">
            {!! $body !!}
        </article>

        <aside class="space-y-5">
            <div class="rounded-3xl bg-ink p-6 text-cream">
                <p class="eyebrow text-cream/60">Need a hand?</p>
                <p class="mt-3 font-display text-2xl font-semibold leading-snug">We answer every message ourselves.</p>
                <a href="mailto:{{ config('cakelle.support_email') }}" class="mt-4 inline-flex text-sm font-semibold text-cream underline decoration-cream/30 underline-offset-4 hover:decoration-cream">{{ config('cakelle.support_email') }}</a>
            </div>

            <div class="card-flat p-5 text-sm leading-6 text-cocoa">
                <p class="font-semibold text-ink">Ordering a cake?</p>
                <p class="mt-2">Start with the marketplace, or track an order you have already placed.</p>
                <div class="mt-4 flex flex-col gap-2">
                    <a href="{{ route('marketplace') }}" class="btn-secondary btn-sm">Browse cakes</a>
                    <a href="{{ route('orders.track.form') }}" class="btn-ghost btn-sm">Track an order</a>
                </div>
            </div>

            @if ($page->get('updated_label'))
                <p class="px-1 text-xs text-cocoa/60">{{ $page->get('updated_label') }}</p>
            @endif
        </aside>
    </section>
@endsection
