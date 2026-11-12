<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PayMongoGateway
{
    public function isConfigured(): bool
    {
        return filled(config('services.paymongo.secret_key'));
    }

    public function createCheckoutSession(Order $order, Payment $payment): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('PayMongo is not configured. Add PAYMONGO_SECRET_KEY to the environment.');
        }

        $currency = config('services.paymongo.currency', 'PHP');
        $lineItems = $order->items->map(fn ($item) => [
            'name' => $item->product_name.($item->variant_name ? ' — '.$item->variant_name : ''),
            'amount' => $this->toCentavos((float) $item->unit_price),
            'currency' => $currency,
            'quantity' => (int) $item->quantity,
        ])->values()->all();

        if ((float) $order->delivery_fee > 0) {
            $lineItems[] = [
                'name' => 'Delivery fee',
                'amount' => $this->toCentavos((float) $order->delivery_fee),
                'currency' => $currency,
                'quantity' => 1,
            ];
        }

        $paymentMethods = config('services.paymongo.payment_methods', ['qrph', 'card']);
        if ($paymentMethods === []) {
            $paymentMethods = ['qrph', 'card'];
        }

        $response = Http::withBasicAuth((string) config('services.paymongo.secret_key'), '')
            ->acceptJson()
            ->asJson()
            ->timeout(25)
            ->withHeaders(['Idempotency-Key' => 'cakelle-checkout-'.$order->id])
            ->post(rtrim((string) config('services.paymongo.base_url'), '/').'/v2/checkout_sessions', [
                'data' => [
                    'attributes' => [
                        'line_items' => $lineItems,
                        'payment_method_types' => $paymentMethods,
                        'success_url' => route('payment.return', ['order' => $order->order_number, 'token' => $order->public_token]),
                        'cancel_url' => route('orders.show', ['order' => $order->order_number, 'token' => $order->public_token]),
                        'reference_number' => $order->order_number,
                        'description' => 'Cakelle order '.$order->order_number.' from '.$order->shop->name,
                        'send_email_receipt' => true,
                        'show_description' => true,
                        'show_line_items' => true,
                        'metadata' => [
                            'order_id' => (string) $order->id,
                            'shop_id' => (string) $order->shop_id,
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            Log::warning('PayMongo checkout creation failed', [
                'order' => $order->order_number,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);
            $message = $response->json('errors.0.detail') ?? 'PayMongo could not create a payment session.';
            throw new RuntimeException($message);
        }

        $data = $response->json('data');

        return [
            'id' => $data['id'] ?? null,
            'checkout_url' => data_get($data, 'attributes.checkout_url'),
            'livemode' => (bool) data_get($data, 'attributes.livemode', false),
            'raw' => $response->json(),
        ];
    }

    public function retrieveCheckoutSession(string $checkoutId): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::withBasicAuth((string) config('services.paymongo.secret_key'), '')
                ->acceptJson()
                ->timeout(20)
                ->get(rtrim((string) config('services.paymongo.base_url'), '/').'/v1/checkout_sessions/'.$checkoutId);

            return $response->successful() ? $response->json('data') : null;
        } catch (RequestException $exception) {
            Log::warning('PayMongo checkout retrieval failed', ['message' => $exception->getMessage()]);

            return null;
        }
    }

    public function verifyWebhook(string $rawBody, ?string $signatureHeader): array
    {
        $secret = (string) config('services.paymongo.webhook_secret');
        if ($secret === '') {
            throw new RuntimeException('PayMongo webhook secret is not configured.');
        }

        if (! $signatureHeader) {
            throw new RuntimeException('Missing Paymongo-Signature header.');
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($key !== null) {
                $parts[$key][] = (string) $value;
            }
        }

        $timestamp = $parts['t'][0] ?? null;
        if (! $timestamp) {
            throw new RuntimeException('Invalid Paymongo-Signature header.');
        }

        $tolerance = (int) env('PAYMONGO_WEBHOOK_TOLERANCE', 300);
        if ($tolerance > 0 && abs(time() - (int) $timestamp) > $tolerance) {
            throw new RuntimeException('Webhook timestamp is outside the allowed tolerance.');
        }

        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) {
            throw new RuntimeException('Invalid webhook payload.');
        }

        $live = (bool) data_get($payload, 'data.attributes.livemode', false);
        $provided = $live ? ($parts['li'][0] ?? null) : ($parts['te'][0] ?? null);
        if (! $provided) {
            throw new RuntimeException('No matching webhook signature found for this mode.');
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);
        if (! hash_equals($expected, $provided)) {
            throw new RuntimeException('Webhook signature verification failed.');
        }

        return $payload;
    }

    public function toCentavos(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
