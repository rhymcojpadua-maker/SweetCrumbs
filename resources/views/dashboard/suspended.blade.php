@extends('layouts.dashboard')

@section('title', 'Bakery suspended')
@section('eyebrow', 'Account notice')
@section('heading', 'Bakery suspended')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="card p-6 sm:p-9">
            <span class="badge {{ $shop->status->badgeClasses() }}">{{ $shop->status->label() }}</span>
            <h2 class="mt-4 font-display text-3xl font-semibold">{{ $shop->name }} is paused.</h2>
            <p class="mt-3 text-sm leading-7 text-cocoa">Your bakery is temporarily unable to receive new orders. Existing order records, products, and customer history are preserved. Existing customers can still view past order pages.</p>

            <p class="mt-6 text-sm leading-7 text-cocoa">If you believe this is a mistake, contact the marketplace team at <a href="mailto:{{ config('cakelle.support_email') }}" class="font-semibold text-rose hover:underline">{{ config('cakelle.support_email') }}</a> with your bakery name and account email.</p>

            <div class="mt-7 flex flex-wrap gap-3">
                <a href="mailto:{{ config('cakelle.support_email') }}" class="btn-primary btn-sm">Contact support</a>
                <a href="{{ route('home') }}" class="btn-secondary btn-sm">Back to marketplace</a>
            </div>
        </div>
    </div>
@endsection
