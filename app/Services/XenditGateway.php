<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class XenditGateway
{
    public function isConfigured(): bool
    {
        return filled(config('services.xendit.secret_key'));
    }

    public function createCheckoutSession(Order $order, Payment $payment): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Xendit is not configured. Add XENDIT_SECRET_KEY to the environment.');
        }

        $currency = config('services.xendit.currency', 'PHP');
        $appUrl = rtrim((string) config('app.url'), '/');
        $appUrlParts = parse_url($appUrl);

        if (strtolower((string) ($appUrlParts['scheme'] ?? '')) !== 'https' || empty($appUrlParts['host'])) {
            throw new RuntimeException('Xendit checkout requires APP_URL to be set to a public HTTPS URL.');
        }

        $items = $order->items->map(fn ($item) => [
            'reference_id' => 'orderitem'.$item->id,
            'name' => $item->product_name.($item->variant_name ? ' — '.$item->variant_name : ''),
            'type' => 'PHYSICAL_PRODUCT',
            'category' => 'Cakes',
            'net_unit_amount' => (float) $item->unit_price,
            'quantity' => (int) $item->quantity,
            'currency' => $currency,
        ])->values()->all();

        if ((float) $order->delivery_fee > 0) {
            $items[] = [
                'reference_id' => 'delivery'.$order->id,
                'name' => 'Delivery fee',
                'type' => 'FEE',
                'category' => 'Delivery',
                'net_unit_amount' => (float) $order->delivery_fee,
                'quantity' => 1,
                'currency' => $currency,
            ];
        }

        $attributes = [
            'reference_id' => $payment->reference_number,
            'session_type' => 'PAY',
            'mode' => 'PAYMENT_LINK',
            'amount' => (float) $payment->amount,
            'currency' => $currency,
            'country' => 'PH',
            'locale' => 'en',
            'description' => 'SweetCrumbs order '.$order->order_number.' from '.$order->shop->name,
            'success_return_url' => $appUrl.'/'.ltrim(route('payment.return', [
                'order' => $order->order_number,
                'token' => $order->public_token,
            ], false), '/'),
            'cancel_return_url' => $appUrl.'/'.ltrim(route('orders.show', [
                'order' => $order->order_number,
                'token' => $order->public_token,
            ], false), '/'),
            'expires_at' => now()->addMinutes((int) config('cakelle.order_payment_window_minutes', 45))->toIso8601String(),
            'metadata' => [
                'order_id' => (string) $order->id,
                'shop_id' => (string) $order->shop_id,
            ],
        ];

        if ($items !== []) {
            $attributes['items'] = $items;
        }

        $allowedChannels = config('services.xendit.allowed_payment_channels', []);
        if ($allowedChannels !== []) {
            $attributes['allowed_payment_channels'] = $allowedChannels;
        }

        $response = Http::withBasicAuth((string) config('services.xendit.secret_key'), '')
            ->acceptJson()
            ->asJson()
            ->timeout(25)
            ->post(rtrim((string) config('services.xendit.base_url'), '/').'/sessions', $attributes);

        if ($response->failed()) {
            Log::warning('Xendit checkout creation failed', [
                'order' => $order->order_number,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);
            $message = $response->json('message')
                ?? $response->json('error_code')
                ?? 'Xendit could not create a payment session.';
            throw new RuntimeException($message);
        }

        $data = $response->json();
        $checkoutUrl = data_get($data, 'payment_link_url');
        $sessionId = data_get($data, 'payment_session_id');

        if (! $sessionId || ! $checkoutUrl) {
            Log::warning('Xendit checkout response is missing required fields', [
                'order' => $order->order_number,
                'response' => $data,
            ]);

            throw new RuntimeException('Xendit returned an incomplete checkout session.');
        }

        return [
            'id' => $sessionId,
            'checkout_url' => $checkoutUrl,
            'raw' => $data,
        ];
    }

    public function retrieveCheckoutSession(string $sessionId): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::withBasicAuth((string) config('services.xendit.secret_key'), '')
                ->acceptJson()
                ->timeout(20)
                ->get(rtrim((string) config('services.xendit.base_url'), '/').'/sessions/'.urlencode($sessionId));

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Xendit checkout retrieval failed', [
                'session_id' => $sessionId,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            return null;
        } catch (RequestException $exception) {
            Log::warning('Xendit checkout retrieval failed', [
                'session_id' => $sessionId,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function verifyWebhook(string $rawBody, ?string $callbackToken): array
    {
        $secret = (string) config('services.xendit.webhook_token');
        if ($secret === '') {
            throw new RuntimeException('Xendit webhook token is not configured.');
        }

        if (! $callbackToken || ! hash_equals($secret, $callbackToken)) {
            throw new RuntimeException('Xendit callback token verification failed.');
        }

        $payload = json_decode($rawBody, true);
        if (! is_array($payload) || ! is_string($payload['event'] ?? null) || ! is_array($payload['data'] ?? null)) {
            throw new RuntimeException('Invalid Xendit webhook payload.');
        }

        return $payload;
    }

    public function toCentavos(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
