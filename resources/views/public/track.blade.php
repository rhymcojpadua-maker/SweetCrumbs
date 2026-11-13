@extends('layouts.public')

@section('title', 'Track an order — SweetCrumbs')

@section('content')
    <div class="container-page py-16">
        <div class="mx-auto max-w-xl">
            <div class="text-center">
                <p class="eyebrow">Track an order</p>
                <h1 class="mt-3 font-display text-4xl font-semibold tracking-tight sm:text-5xl">Where is the cake?</h1>
                <p class="mt-4 text-cocoa">Enter the order number and the email used at checkout. We will take you to the private order page.</p>
            </div>

            <form method="POST" action="{{ route('orders.track') }}" class="mt-8 card p-6 sm:p-8">
                @csrf
                <div>
                    <label for="order_number" class="label">Order number</label>
                    <input type="text" name="order_number" id="order_number" value="{{ old('order_number') }}" required class="input uppercase" placeholder="CK-261109-ABCDE">
                </div>
                <div class="mt-4">
                    <label for="email" class="label">Email address</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required class="input" placeholder="you@example.com">
                </div>
                <button type="submit" class="mt-5 btn-primary w-full">Find my order</button>
            </form>

            <div class="mt-6 card-flat p-5 text-sm leading-6 text-cocoa">
                <p class="font-semibold text-ink">Where do I find my order number?</p>
                <p class="mt-2">It starts with <span class="font-mono text-xs">CK-</span> and was shown on the confirmation page after checkout. If you saved the private order link, you can open it directly.</p>
            </div>
        </div>
    </div>
@endsection
