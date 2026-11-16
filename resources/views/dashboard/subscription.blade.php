@extends('layouts.dashboard')

@section('title', 'Subscription')
@section('eyebrow', 'Bakery settings')
@section('heading', 'Subscription')

@section('content')
    <div class="grid gap-5 xl:grid-cols-[1.4fr_1fr]">
        <section class="card p-5 sm:p-7">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cocoa/70">SweetCrumbs bakery plan</p>

            @if ($monthlyPrice === null)
                <h2 class="mt-3 font-display text-2xl font-semibold">Subscriptions are not enabled yet</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-cocoa">
                    The platform administrator has not set a monthly price. You will not be charged, and your bakery access will remain unchanged.
                </p>
                <span class="mt-5 inline-flex rounded-full bg-sage/10 px-3 py-1 text-sm font-semibold text-sage">No payment due</span>
            @else
                <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2 class="font-display text-3xl font-semibold">₱{{ number_format($monthlyPrice, 2) }} <span class="text-base font-medium text-cocoa">/ month</span></h2>
                        <p class="mt-2 text-sm leading-6 text-cocoa">Renew manually through Xendit. Your next month starts after the current access period ends.</p>
                    </div>
                    @if ($hasAccess && $subscription?->period_ends_at)
                        <span class="rounded-full bg-sage/10 px-3 py-1.5 text-sm font-semibold text-sage">
                            {{ $subscription->status === 'trialing' ? 'Grace period' : 'Active' }} through {{ $subscription->period_ends_at->format('M j, Y') }}
                        </span>
                    @else
                        <span class="rounded-full bg-rose/10 px-3 py-1.5 text-sm font-semibold text-rose">Payment required</span>
                    @endif
                </div>

                @if (! $hasAccess)
                    <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">
                        Your bakery storefront and dashboard are paused until payment is confirmed. Existing bakery accounts receive a 30-day grace period when pricing is first configured.
                    </div>
                @endif

                @if (! $checkoutConfigured)
                    <p class="mt-5 text-sm text-rose">Subscription checkout is not ready yet. Please contact the platform administrator.</p>
                @else
                    <form method="POST" action="{{ route('dashboard.subscription.checkout') }}" class="mt-6">
                        @csrf
                        <button type="submit" class="btn-primary">
                            @if ($latestPayment?->status === 'pending')
                                Continue checkout for ₱{{ number_format((float) $latestPayment->amount, 2) }}
                            @else
                                {{ $hasAccess ? 'Renew for ₱'.number_format($monthlyPrice, 2) : 'Subscribe for ₱'.number_format($monthlyPrice, 2) }}
                            @endif
                        </button>
                    </form>
                @endif
            @endif

            @if ($latestPayment?->status === 'pending')
                <p class="mt-4 text-sm text-cocoa">
                    A checkout is already in progress. Use the button above to continue it before it expires
                    @if ($latestPayment->expires_at)
                        on {{ $latestPayment->expires_at->format('M j, Y g:i A') }}
                    @endif.
                </p>
            @elseif ($latestPayment?->status === 'expired' || $latestPayment?->status === 'failed')
                <p class="mt-4 text-sm text-cocoa">Your last checkout {{ $latestPayment->status }}. You can start a new one when ready.</p>
            @endif
        </section>

        <aside class="card-flat p-5 sm:p-6">
            <h2 class="font-display text-xl font-semibold">How billing works</h2>
            <ul class="mt-4 space-y-3 text-sm leading-6 text-cocoa">
                <li>There is no charge while the administrator leaves the price unset.</li>
                <li>Subscription payments are one month at a time; Xendit does not automatically renew them.</li>
                <li>Access resumes only after Xendit confirms the payment.</li>
            </ul>
        </aside>
    </div>

    @if ($subscription && $subscription->payments->isNotEmpty())
        <section class="card mt-5 overflow-hidden">
            <div class="border-b border-ink/8 px-5 py-4 sm:px-6">
                <h2 class="font-display text-xl font-semibold">Recent subscription payments</h2>
            </div>
            <div class="divide-y divide-ink/8">
                @foreach ($subscription->payments as $payment)
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
                        <div>
                            <p class="text-sm font-semibold">₱{{ number_format((float) $payment->amount, 2) }} <span class="font-normal text-cocoa">· {{ $payment->created_at->format('M j, Y') }}</span></p>
                            <p class="mt-1 text-xs text-cocoa">Reference {{ $payment->reference_number }}</p>
                        </div>
                        <span class="badge {{ $payment->status === 'paid' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : ($payment->status === 'pending' ? 'bg-amber-50 text-amber-700 ring-amber-200' : 'bg-stone-100 text-stone-600 ring-stone-200') }}">
                            {{ ucfirst($payment->status) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
@endsection
