<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        private readonly XenditGateway $gateway,
        private readonly InventoryService $inventoryService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function startCheckout(Order $order, Payment $payment): Payment
    {
        try {
            $session = $this->gateway->createCheckoutSession($order, $payment);

            DB::transaction(function () use ($order, $payment, $session) {
                $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
                $alreadyPaid = $payment->status === PaymentStatus::Paid;

                $payment->update([
                    'provider' => 'xendit',
                    'provider_checkout_id' => $session['id'],
                    'checkout_url' => $session['checkout_url'],
                    'status' => $alreadyPaid ? PaymentStatus::Paid : PaymentStatus::Pending,
                    'expires_at' => now()->addMinutes((int) config('cakelle.order_payment_window_minutes', 45)),
                    'metadata' => $session['raw'],
                    'failure_reason' => null,
                    'failed_at' => null,
                ]);

                if (! $alreadyPaid) {
                    $order->update(['payment_status' => PaymentStatus::Pending]);
                }

                PaymentTransaction::create([
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'provider' => 'xendit',
                    'type' => 'checkout_created',
                    'status' => 'pending',
                    'amount' => $payment->amount,
                    'payload' => $session['raw'],
                ]);
            });

            return $payment->fresh();
        } catch (\RuntimeException|ConnectionException $exception) {
            DB::transaction(function () use ($payment, $exception): void {
                $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

                if ($lockedPayment->status !== PaymentStatus::Paid) {
                    $lockedPayment->update([
                        'provider' => 'xendit',
                        'status' => PaymentStatus::Failed,
                        'failure_reason' => $exception->getMessage(),
                        'failed_at' => now(),
                    ]);
                }
            });

            throw $exception;
        }
    }

    public function processWebhook(array $payload): array
    {
        $type = data_get($payload, 'event');
        $session = data_get($payload, 'data');

        if (! is_string($type) || ! is_array($session)) {
            return ['result' => 'ignored', 'reason' => 'Unsupported payload.'];
        }

        $sessionId = data_get($session, 'payment_session_id');
        $eventId = data_get($payload, 'id')
            ?? ($sessionId ? $type.':'.$sessionId : null);

        if ($eventId && PaymentTransaction::query()->where('provider_event_id', $eventId)->exists()) {
            return ['result' => 'duplicate'];
        }

        return match ($type) {
            'payment_session.completed' => $this->handleSessionCompleted($session, $payload, $eventId),
            'payment_session.expired' => $this->handleSessionExpired($session, $payload, $eventId),
            default => $this->recordIgnored($type, $session, $payload, $eventId),
        };
    }

    public function syncFromProvider(Payment $payment): Payment
    {
        if (! $payment->provider_checkout_id) {
            return $payment;
        }

        $session = $this->gateway->retrieveCheckoutSession($payment->provider_checkout_id);
        if (! $session) {
            return $payment;
        }

        $status = data_get($session, 'status');
        $event = match ($status) {
            'COMPLETED' => 'payment_session.completed',
            'EXPIRED', 'CANCELED' => 'payment_session.expired',
            default => null,
        };

        if ($event) {
            $this->processWebhook(['event' => $event, 'data' => $session]);
        }

        return $payment->fresh();
    }

    private function handleSessionCompleted(array $session, array $payload, ?string $eventId): array
    {
        if (data_get($session, 'status') !== 'COMPLETED') {
            return ['result' => 'ignored', 'reason' => 'Session is not completed.'];
        }

        $reference = data_get($session, 'reference_id');
        $amount = data_get($session, 'amount');
        $sessionId = data_get($session, 'payment_session_id');
        $paymentId = data_get($session, 'payment_id');

        if (! $reference || ! is_numeric($amount) || (float) $amount <= 0) {
            return ['result' => 'ignored', 'reason' => 'Missing session reference or amount.'];
        }

        return $this->markPaid(
            (string) $reference,
            (float) $amount,
            is_string($paymentId) ? $paymentId : null,
            null,
            $payload,
            $eventId,
            is_string($sessionId) ? $sessionId : null,
        );
    }

    private function handleSessionExpired(array $session, array $payload, ?string $eventId): array
    {
        $reference = data_get($session, 'reference_id');
        $sessionId = data_get($session, 'payment_session_id');
        $status = data_get($session, 'status');

        if (! $reference || ! in_array($status, ['EXPIRED', 'CANCELED'], true)) {
            return ['result' => 'ignored', 'reason' => 'Session is not expired or canceled.'];
        }

        $payment = Payment::query()->where('reference_number', $reference)->first();
        if (! $payment) {
            return $this->recordIgnored('payment_session.expired', $session, $payload, $eventId);
        }

        return DB::transaction(function () use ($payment, $sessionId, $payload, $eventId) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);

            if ($eventId && PaymentTransaction::query()->where('provider_event_id', $eventId)->exists()) {
                return ['result' => 'duplicate'];
            }

            if ($payment->status === PaymentStatus::Paid) {
                return ['result' => 'ignored', 'reason' => 'Payment already paid.'];
            }

            if ($payment->provider_checkout_id && $sessionId && $payment->provider_checkout_id !== $sessionId) {
                return ['result' => 'ignored', 'reason' => 'Session does not match the current checkout.'];
            }

            $payment->update([
                'provider' => 'xendit',
                'provider_checkout_id' => $sessionId ?: $payment->provider_checkout_id,
                'status' => PaymentStatus::Expired,
                'failed_at' => now(),
                'failure_reason' => 'Xendit payment session expired.',
            ]);

            if ($order->status === OrderStatus::PendingPayment) {
                $order->status = OrderStatus::Cancelled;
                $order->payment_status = PaymentStatus::Expired;
                $order->cancelled_at = now();
                $order->save();
                $this->inventoryService->release($order, 'Xendit payment session expired');
            }

            $this->recordTransaction($payment, $order, $payload, $eventId, 'checkout_expired', 'expired');

            return ['result' => 'processed'];
        });
    }

    private function markPaid(
        string $reference,
        float $amount,
        ?string $providerPaymentId,
        ?string $method,
        array $payload,
        ?string $eventId,
        ?string $sessionId,
    ): array {
        return DB::transaction(function () use ($reference, $amount, $providerPaymentId, $method, $payload, $eventId, $sessionId) {
            $payment = Payment::query()->where('reference_number', $reference)->lockForUpdate()->first();
            if (! $payment) {
                return ['result' => 'ignored', 'reason' => 'Payment not found.'];
            }

            if ($eventId && PaymentTransaction::query()->where('provider_event_id', $eventId)->exists()) {
                return ['result' => 'duplicate'];
            }

            $order = Order::query()->lockForUpdate()->find($payment->order_id);
            if (! $order) {
                return ['result' => 'ignored', 'reason' => 'Order not found.'];
            }

            if ($payment->provider_checkout_id && $sessionId && $payment->provider_checkout_id !== $sessionId) {
                return ['result' => 'ignored', 'reason' => 'Session does not match the current checkout.'];
            }

            $expected = $this->gateway->toCentavos((float) $payment->amount);
            $received = $this->gateway->toCentavos($amount);
            if ($received !== $expected) {
                Log::warning('Xendit amount mismatch', [
                    'payment' => $payment->id,
                    'expected_centavos' => $expected,
                    'received_centavos' => $received,
                ]);

                return ['result' => 'ignored', 'reason' => 'Amount mismatch.'];
            }

            if ($payment->status === PaymentStatus::Paid) {
                $this->recordTransaction($payment, $order, $payload, $eventId, 'webhook_duplicate', 'paid');

                return ['result' => 'duplicate'];
            }

            $payment->update([
                'provider' => 'xendit',
                'provider_checkout_id' => $sessionId ?: $payment->provider_checkout_id,
                'provider_payment_id' => $providerPaymentId ?: $payment->provider_payment_id,
                'payment_method' => $method,
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'failure_reason' => null,
                'metadata' => array_merge($payment->metadata ?? [], ['last_paid_payload' => $payload]),
            ]);

            $order->payment_status = PaymentStatus::Paid;
            if ($order->status === OrderStatus::PendingPayment) {
                $order->status = OrderStatus::PendingConfirmation;
            }
            $order->save();

            $this->recordTransaction($payment, $order, $payload, $eventId, 'webhook', 'paid');

            $order->shop->owner->appNotifications()->create([
                'shop_id' => $order->shop_id,
                'type' => 'payment_received',
                'title' => 'Payment received',
                'body' => 'Payment for order '.$order->order_number.' was confirmed by Xendit.',
                'data' => ['order_id' => $order->id],
            ]);

            if ($order->customer_id) {
                $order->customer->appNotifications()->create([
                    'shop_id' => $order->shop_id,
                    'type' => 'payment_confirmed',
                    'title' => 'Payment confirmed',
                    'body' => 'We received your payment for order '.$order->order_number.'.',
                    'data' => ['order_id' => $order->id],
                ]);
            }

            $this->activityLogger->log(
                'payment.paid',
                'Payment confirmed for order '.$order->order_number,
                $order,
                $order->shop,
                ['payment_id' => $payment->id, 'method' => $method]
            );

            return ['result' => 'processed'];
        });
    }

    private function recordIgnored(string $type, array $resource, array $payload, ?string $eventId): array
    {
        PaymentTransaction::create([
            'order_id' => null,
            'payment_id' => null,
            'provider' => 'xendit',
            'provider_event_id' => $eventId,
            'type' => $type,
            'status' => 'ignored',
            'payload' => $payload,
        ]);

        return ['result' => 'ignored', 'reason' => 'No matching order.'];
    }

    private function recordTransaction(Payment $payment, Order $order, array $payload, ?string $eventId, string $type, string $status): void
    {
        PaymentTransaction::create([
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'provider' => 'xendit',
            'provider_event_id' => $eventId,
            'type' => $type,
            'status' => $status,
            'amount' => $payment->amount,
            'payload' => $payload,
        ]);
    }
}
