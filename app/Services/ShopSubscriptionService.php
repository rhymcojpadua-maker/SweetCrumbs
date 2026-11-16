<?php

namespace App\Services;

use App\Enums\ShopStatus;
use App\Models\PlatformSetting;
use App\Models\Shop;
use App\Models\ShopSubscription;
use App\Models\ShopSubscriptionPayment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class ShopSubscriptionService
{
    public function __construct(private readonly XenditGateway $gateway) {}

    public function monthlyPrice(): ?float
    {
        $price = PlatformSetting::get('bakery_subscription_monthly_price');

        return is_numeric($price) && (float) $price > 0 ? (float) $price : null;
    }

    public function hasAccess(Shop $shop): bool
    {
        if ($this->monthlyPrice() === null) {
            return true;
        }

        return $shop->subscription?->hasAccess() ?? false;
    }

    public function grantTrialIfNeeded(Shop $shop): bool
    {
        if ($this->monthlyPrice() === null) {
            return false;
        }

        $subscription = $shop->subscription()->firstOrCreate([], [
            'status' => 'trialing',
            'period_ends_at' => now()->addDays(30),
        ]);

        return $subscription->wasRecentlyCreated;
    }

    public function grantExistingShopsGracePeriod(): void
    {
        Shop::query()
            ->where('status', ShopStatus::Active->value)
            ->eachById(fn (Shop $shop) => $this->grantTrialIfNeeded($shop));
    }

    public function startCheckout(Shop $shop): ShopSubscriptionPayment
    {
        $amount = $this->monthlyPrice();
        if ($amount === null) {
            throw new RuntimeException('Bakery subscription pricing is not configured.');
        }

        $checkoutException = null;
        $payment = DB::transaction(function () use ($shop, $amount, &$checkoutException) {
            $subscription = $shop->subscription()
                ->lockForUpdate()
                ->firstOrCreate([], ['status' => 'expired']);
            $pendingPayment = $subscription->payments()
                ->where('status', 'pending')
                ->whereNotNull('checkout_url')
                ->where('expires_at', '>', now())
                ->latest()
                ->first();

            if ($pendingPayment) {
                return $pendingPayment;
            }

            $currency = (string) config('services.xendit.currency', 'PHP');
            $payment = $subscription->payments()->create([
                'provider' => 'xendit',
                'reference_number' => 'SC-SUB-'.Str::upper(Str::random(20)),
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'pending',
                'expires_at' => now()->addMinutes((int) config('cakelle.order_payment_window_minutes', 45)),
            ]);

            try {
                $session = $this->gateway->createSubscriptionCheckoutSession($payment, $shop);
                $payment->update([
                    'provider_checkout_id' => $session['id'],
                    'checkout_url' => $session['checkout_url'],
                    'payload' => $session['raw'],
                ]);
            } catch (RuntimeException|ConnectionException $exception) {
                $payment->update([
                    'status' => 'failed',
                    'payload' => ['checkout_error' => $exception->getMessage()],
                ]);
                $checkoutException = $exception;
            }

            return $payment->fresh();
        });

        if ($checkoutException) {
            throw $checkoutException;
        }

        return $payment;
    }

    public function syncFromProvider(ShopSubscription $subscription): ?ShopSubscriptionPayment
    {
        $payment = $subscription->payments()
            ->where('status', 'pending')
            ->whereNotNull('provider_checkout_id')
            ->latest()
            ->first();

        if (! $payment) {
            return $payment;
        }

        $session = $this->gateway->retrieveCheckoutSession($payment->provider_checkout_id);
        if (! $session) {
            return $payment;
        }

        $type = match (data_get($session, 'status')) {
            'COMPLETED' => 'payment_session.completed',
            'EXPIRED', 'CANCELED' => 'payment_session.expired',
            default => null,
        };

        if ($type) {
            $this->processWebhook(['event' => $type, 'data' => $session]);
        }

        return $payment->fresh();
    }

    public function processWebhook(array $payload): array
    {
        $type = data_get($payload, 'event');
        $session = data_get($payload, 'data');
        if (! is_string($type) || ! is_array($session)) {
            return ['result' => 'ignored', 'reason' => 'Unsupported subscription payload.'];
        }

        $sessionId = data_get($session, 'payment_session_id');
        $providerEventId = data_get($payload, 'id');
        $eventId = is_string($providerEventId)
            ? $providerEventId
            : ($sessionId ? $type.':'.$sessionId : null);

        return match ($type) {
            'payment_session.completed' => $this->handleSessionCompleted($session, $payload, $eventId),
            'payment_session.expired' => $this->handleSessionExpired($session, $payload, $eventId),
            default => ['result' => 'ignored', 'reason' => 'Unsupported subscription event.'],
        };
    }

    private function handleSessionCompleted(array $session, array $payload, ?string $eventId): array
    {
        $reference = data_get($session, 'reference_id');
        $amount = data_get($session, 'amount');
        $sessionId = data_get($session, 'payment_session_id');

        if (data_get($session, 'status') !== 'COMPLETED'
            || ! is_string($reference)
            || ! is_numeric($amount)
            || (float) $amount <= 0) {
            Log::warning('Invalid Xendit subscription completion event', [
                'reference_id' => $reference,
                'session_id' => $sessionId,
            ]);

            return ['result' => 'ignored', 'reason' => 'Invalid completed subscription session.'];
        }

        return DB::transaction(function () use ($reference, $amount, $sessionId, $payload, $eventId) {
            if ($eventId && ShopSubscriptionPayment::query()->where('provider_event_id', $eventId)->exists()) {
                return ['result' => 'duplicate'];
            }

            $payment = ShopSubscriptionPayment::query()
                ->where('reference_number', $reference)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                Log::warning('Xendit subscription payment reference was not found', [
                    'reference_id' => $reference,
                    'session_id' => $sessionId,
                ]);

                return ['result' => 'ignored', 'reason' => 'Subscription payment not found.'];
            }

            if ($payment->status !== 'pending') {
                return ['result' => $payment->status === 'paid' ? 'duplicate' : 'ignored'];
            }

            if ($payment->provider_checkout_id && $sessionId && $payment->provider_checkout_id !== $sessionId) {
                Log::warning('Xendit subscription session does not match checkout', [
                    'reference_id' => $reference,
                    'expected_session_id' => $payment->provider_checkout_id,
                    'received_session_id' => $sessionId,
                ]);

                return ['result' => 'ignored', 'reason' => 'Session does not match the current subscription checkout.'];
            }

            if ((int) round((float) $amount * 100) !== (int) round((float) $payment->amount * 100)) {
                Log::warning('Xendit subscription amount mismatch', [
                    'reference_id' => $reference,
                    'expected_centavos' => (int) round((float) $payment->amount * 100),
                    'received_centavos' => (int) round((float) $amount * 100),
                ]);

                return ['result' => 'ignored', 'reason' => 'Subscription amount mismatch.'];
            }

            $subscription = ShopSubscription::query()
                ->lockForUpdate()
                ->findOrFail($payment->shop_subscription_id);
            $periodStartsAt = $subscription->period_ends_at?->isFuture()
                ? $subscription->period_ends_at
                : now();

            $payment->update([
                'status' => 'paid',
                'provider_checkout_id' => $sessionId ?: $payment->provider_checkout_id,
                'provider_event_id' => $eventId,
                'payload' => $payload,
                'paid_at' => now(),
            ]);
            $subscription->update([
                'status' => 'active',
                'period_ends_at' => $periodStartsAt->copy()->addMonth(),
            ]);

            $shop = $subscription->shop()->with('owner')->firstOrFail();
            $shop->owner->appNotifications()->create([
                'shop_id' => $shop->id,
                'type' => 'subscription_paid',
                'title' => 'Bakery subscription activated',
                'body' => 'Your SweetCrumbs subscription is active through '.$subscription->period_ends_at->format('M j, Y').'.',
                'data' => ['subscription_id' => $subscription->id, 'payment_id' => $payment->id],
            ]);

            return ['result' => 'processed'];
        });
    }

    private function handleSessionExpired(array $session, array $payload, ?string $eventId): array
    {
        $reference = data_get($session, 'reference_id');
        $sessionId = data_get($session, 'payment_session_id');

        if (! is_string($reference) || ! in_array(data_get($session, 'status'), ['EXPIRED', 'CANCELED'], true)) {
            Log::warning('Invalid Xendit subscription expiry event', [
                'reference_id' => $reference,
                'session_id' => $sessionId,
            ]);

            return ['result' => 'ignored', 'reason' => 'Invalid expired subscription session.'];
        }

        return DB::transaction(function () use ($reference, $sessionId, $payload, $eventId) {
            if ($eventId && ShopSubscriptionPayment::query()->where('provider_event_id', $eventId)->exists()) {
                return ['result' => 'duplicate'];
            }

            $payment = ShopSubscriptionPayment::query()
                ->where('reference_number', $reference)
                ->lockForUpdate()
                ->first();

            if (! $payment || $payment->status !== 'pending') {
                return ['result' => 'ignored'];
            }

            if ($payment->provider_checkout_id && $sessionId && $payment->provider_checkout_id !== $sessionId) {
                Log::warning('Expired Xendit subscription session does not match checkout', [
                    'reference_id' => $reference,
                    'expected_session_id' => $payment->provider_checkout_id,
                    'received_session_id' => $sessionId,
                ]);

                return ['result' => 'ignored', 'reason' => 'Session does not match the current subscription checkout.'];
            }

            $payment->update([
                'status' => 'expired',
                'provider_checkout_id' => $sessionId ?: $payment->provider_checkout_id,
                'provider_event_id' => $eventId,
                'payload' => $payload,
            ]);

            return ['result' => 'processed'];
        });
    }
}
