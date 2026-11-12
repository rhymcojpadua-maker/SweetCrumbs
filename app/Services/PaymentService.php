<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaymentService
{
    public function __construct(
        private readonly PayMongoGateway $gateway,
        private readonly InventoryService $inventoryService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function startCheckout(Order $order, Payment $payment): Payment
    {
        try {
            $session = $this->gateway->createCheckoutSession($order, $payment);

            $payment->update([
                'provider_checkout_id' => $session['id'],
                'checkout_url' => $session['checkout_url'],
                'status' => PaymentStatus::Pending,
                'expires_at' => now()->addMinutes((int) config('cakelle.order_payment_window_minutes', 45)),
                'metadata' => $session['raw'],
                'failure_reason' => null,
            ]);

            $order->update(['payment_status' => PaymentStatus::Pending]);

            PaymentTransaction::create([
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'provider' => 'paymongo',
                'type' => 'checkout_created',
                'status' => 'pending',
                'amount' => $payment->amount,
                'payload' => $session['raw'],
            ]);

            return $payment->fresh();
        } catch (RuntimeException $exception) {
            $payment->update([
                'status' => PaymentStatus::Failed,
                'failure_reason' => $exception->getMessage(),
                'failed_at' => now(),
            ]);

            throw $exception;
        }
    }

    public function processWebhook(array $payload): array
    {
        $eventId = data_get($payload, 'data.id');
        $type = data_get($payload, 'data.attributes.type') ?? data_get($payload, 'data.type');
        $eventData = data_get($payload, 'data.attributes.data') ?? data_get($payload, 'data.data');

        if (! $type || ! is_array($eventData)) {
            return ['result' => 'ignored', 'reason' => 'Unsupported payload.'];
        }

        if ($eventId && PaymentTransaction::query()->where('provider_event_id', $eventId)->exists()) {
            return ['result' => 'duplicate'];
        }

        return match ($type) {
            'checkout_session.payment.paid' => $this->handleCheckoutPaid($eventData, $payload, $eventId),
            'payment.paid' => $this->handlePaymentPaid($eventData, $payload, $eventId),
            'payment.failed' => $this->handlePaymentFailed($eventData, $payload, $eventId),
            'payment.refunded' => $this->handleRefunded($eventData, $payload, $eventId),
            default => $this->recordIgnored($type, $eventData, $payload, $eventId),
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

        $paid = collect(data_get($session, 'attributes.payments', []))
            ->contains(fn ($item) => data_get($item, 'attributes.status') === 'paid');

        if ($paid) {
            $reference = data_get($session, 'attributes.reference_number');
            $this->handleCheckoutPaid($session, ['source' => 'server_sync', 'reference_number' => $reference], null);

            return $payment->fresh();
        }

        return $payment;
    }

    private function handleCheckoutPaid(array $session, array $payload, ?string $eventId): array
    {
        $reference = data_get($session, 'attributes.reference_number') ?? data_get($session, 'reference_number');
        if (! $reference) {
            return ['result' => 'ignored', 'reason' => 'Missing checkout reference.'];
        }

        $payments = data_get($session, 'attributes.payments', []);
        $paidPayment = collect($payments)->first(fn ($item) => data_get($item, 'attributes.status') === 'paid');
        $amountInCentavos = (int) data_get($paidPayment, 'attributes.amount', 0);
        $paymentId = data_get($paidPayment, 'id');
        $method = data_get($paidPayment, 'attributes.source.type');

        return $this->markPaid($reference, $amountInCentavos, $paymentId, $method, $payload, $eventId);
    }

    private function handlePaymentPaid(array $resource, array $payload, ?string $eventId): array
    {
        $paymentId = data_get($resource, 'id');
        $reference = data_get($resource, 'attributes.metadata.order_reference')
            ?? data_get($resource, 'attributes.reference_number')
            ?? data_get($resource, 'attributes.description');

        $payment = Payment::query()
            ->when($reference, fn ($query) => $query->where('reference_number', $reference))
            ->when(! $reference && $paymentId, fn ($query) => $query->where('provider_payment_id', $paymentId))
            ->first();

        if (! $payment) {
            return $this->recordIgnored('payment.paid', $resource, $payload, $eventId);
        }

        return $this->markPaid(
            $payment->reference_number,
            (int) data_get($resource, 'attributes.amount', 0),
            $paymentId,
            data_get($resource, 'attributes.source.type'),
            $payload,
            $eventId
        );
    }

    private function markPaid(string $reference, int $amountInCentavos, ?string $providerPaymentId, ?string $method, array $payload, ?string $eventId): array
    {
        return DB::transaction(function () use ($reference, $amountInCentavos, $providerPaymentId, $method, $payload, $eventId) {
            $payment = Payment::query()->where('reference_number', $reference)->lockForUpdate()->first();
            if (! $payment) {
                return ['result' => 'ignored', 'reason' => 'Payment not found.'];
            }

            $order = Order::query()->lockForUpdate()->find($payment->order_id);
            if (! $order) {
                return ['result' => 'ignored', 'reason' => 'Order not found.'];
            }

            $expected = $this->gateway->toCentavos((float) $payment->amount);
            if ($amountInCentavos > 0 && $amountInCentavos < $expected) {
                Log::warning('PayMongo amount mismatch', [
                    'payment' => $payment->id,
                    'expected_centavos' => $expected,
                    'received_centavos' => $amountInCentavos,
                ]);

                return ['result' => 'ignored', 'reason' => 'Amount mismatch.'];
            }

            if ($payment->status === PaymentStatus::Paid) {
                $this->recordTransaction($payment, $order, $payload, $eventId, 'webhook_duplicate', 'paid');

                return ['result' => 'duplicate'];
            }

            $payment->update([
                'status' => PaymentStatus::Paid,
                'provider_payment_id' => $providerPaymentId ?: $payment->provider_payment_id,
                'payment_method' => $method,
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
                'body' => 'Payment for order '.$order->order_number.' was confirmed by PayMongo.',
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

    private function handlePaymentFailed(array $resource, array $payload, ?string $eventId): array
    {
        $reference = data_get($resource, 'attributes.metadata.order_reference')
            ?? data_get($resource, 'attributes.reference_number');
        $payment = Payment::query()->when($reference, fn ($q) => $q->where('reference_number', $reference))
            ->orWhere('provider_payment_id', data_get($resource, 'id'))
            ->first();

        if (! $payment) {
            return $this->recordIgnored('payment.failed', $resource, $payload, $eventId);
        }

        return DB::transaction(function () use ($payment, $resource, $payload, $eventId) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);

            if ($payment->status === PaymentStatus::Paid) {
                return ['result' => 'ignored', 'reason' => 'Payment already paid.'];
            }

            $payment->update([
                'status' => PaymentStatus::Failed,
                'failed_at' => now(),
                'failure_reason' => data_get($resource, 'attributes.last_payment_error.message', 'The payment was declined.'),
            ]);

            $order->payment_status = PaymentStatus::Failed;
            if ($order->status === OrderStatus::PendingPayment) {
                $order->status = OrderStatus::Cancelled;
                $order->cancelled_at = now();
            }
            $order->save();
            $this->inventoryService->release($order, 'Payment failed');
            $this->recordTransaction($payment, $order, $payload, $eventId, 'webhook', 'failed');

            return ['result' => 'processed'];
        });
    }

    private function handleRefunded(array $resource, array $payload, ?string $eventId): array
    {
        $paymentId = data_get($resource, 'attributes.payment_id') ?? data_get($resource, 'id');
        $payment = Payment::query()->where('provider_payment_id', $paymentId)->first();
        if (! $payment) {
            return $this->recordIgnored('payment.refunded', $resource, $payload, $eventId);
        }

        DB::transaction(function () use ($payment, $resource, $payload, $eventId) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);
            $refundedAmount = (int) data_get($resource, 'attributes.amount_refunded', 0);
            $fullRefund = $refundedAmount >= $this->gateway->toCentavos((float) $payment->amount);

            $payment->update(['status' => $fullRefund ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded]);
            $order->update(['payment_status' => $fullRefund ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded]);
            $this->recordTransaction($payment, $order, $payload, $eventId, 'webhook', 'refunded');
        });

        return ['result' => 'processed'];
    }

    private function recordIgnored(string $type, array $resource, array $payload, ?string $eventId): array
    {
        PaymentTransaction::create([
            'order_id' => null,
            'payment_id' => null,
            'provider' => 'paymongo',
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
            'provider' => 'paymongo',
            'provider_event_id' => $eventId,
            'type' => $type,
            'status' => $status,
            'amount' => $payment->amount,
            'payload' => $payload,
        ]);
    }
}
