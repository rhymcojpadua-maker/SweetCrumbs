@extends('layouts.dashboard')

@section('title', 'Application status')
@section('eyebrow', 'Bakery application')
@section('heading', 'Application status')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="card overflow-hidden">
            <div class="relative overflow-hidden bg-ink px-6 py-10 text-cream sm:px-10">
                <div class="absolute -right-20 -top-24 h-64 w-64 rounded-full bg-rose/20 blur-3xl"></div>
                <p class="eyebrow text-cream/60">SweetCrumbs bakeries</p>
                <h2 class="mt-3 font-display text-4xl font-semibold">
                    @if ($shop?->status === \App\Enums\ShopStatus::Suspended)
                        Your bakery is paused.
                    @elseif ($shop?->status === \App\Enums\ShopStatus::Rejected)
                        Your application was not approved.
                    @else
                        Your application is in review.
                    @endif
                </h2>
                <p class="mt-3 max-w-xl text-sm leading-7 text-cream/70">
                    @if (! $shop)
                        We could not find a bakery application on this account.
                    @elseif ($shop->status === \App\Enums\ShopStatus::Suspended)
                        The marketplace team has paused {{ $shop->name }} while a concern is reviewed. Your data is safe and nothing has been deleted.
                    @elseif ($shop->status === \App\Enums\ShopStatus::Rejected)
                        We could not approve {{ $shop->name }} at this time. See the note below for context from our team.
                    @else
                        Thank you for applying to sell on SweetCrumbs. A member of our marketplace team reviews every new bakery before it opens. We usually respond within two business days.
                    @endif
                </p>
            </div>

            <div class="px-6 py-8 sm:px-10">
                @if ($shop)
                    <dl class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <dt class="label">Bakery</dt>
                            <dd class="font-semibold">{{ $shop->name }}</dd>
                        </div>
                        <div>
                            <dt class="label">Status</dt>
                            <dd><span class="badge {{ $shop->status->badgeClasses() }}">{{ $shop->status->label() }}</span></dd>
                        </div>
                        <div>
                            <dt class="label">Applied</dt>
                            <dd>{{ $shop->created_at->format('F j, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="label">Location</dt>
                            <dd>{{ $shop->city }}, {{ $shop->province }}</dd>
                        </div>
                    </dl>
                @endif

                @if ($shop?->rejection_reason)
                    <div class="mt-6 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm leading-6 text-cocoa">
                        <p class="font-semibold text-ink">Note from the marketplace team</p>
                        <p class="mt-1">{{ $shop->rejection_reason }}</p>
                    </div>
                @endif

                <div class="mt-8">
                    <h3 class="font-display text-xl font-semibold">What happens next</h3>
                    <ol class="mt-4 space-y-4">
                        <li class="flex gap-3">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-emerald-600 text-xs font-bold text-white">1</span>
                            <div>
                                <p class="text-sm font-semibold">Application received</p>
                                <p class="text-xs text-cocoa">Your account and bakery details are with our team.</p>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full {{ in_array($shop?->status, [\App\Enums\ShopStatus::Active, \App\Enums\ShopStatus::Suspended], true) ? 'bg-emerald-600 text-white' : 'bg-ink text-cream' }} text-xs font-bold">2</span>
                            <div>
                                <p class="text-sm font-semibold">Review</p>
                                <p class="text-xs text-cocoa">We look at your story, location, and contact details. If we need anything, we will email {{ $shop?->email }}.</p>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full {{ $shop?->status === \App\Enums\ShopStatus::Active ? 'bg-emerald-600 text-white' : 'bg-parchment text-cocoa' }} text-xs font-bold">3</span>
                            <div>
                                <p class="text-sm font-semibold">Storefront opens</p>
                                <p class="text-xs text-cocoa">Once approved, you can publish cakes, set your schedule, and receive orders.</p>
                            </div>
                        </li>
                    </ol>
                </div>

                <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-ink/8 pt-6">
                    @if ($shop?->status === \App\Enums\ShopStatus::Suspended)
                        <a href="{{ route('home') }}" class="btn-secondary btn-sm">Back to marketplace</a>
                    @endif
                    <a href="mailto:{{ config('cakelle.support_email') }}" class="btn-ghost btn-sm">Contact the marketplace team</a>
                </div>
            </div>
        </div>

        @if ($shop)
            <p class="mt-4 text-center text-xs text-cocoa">Keep your bakery details accurate. You can update account details from your profile.</p>
        @endif
    </div>
@endsection
