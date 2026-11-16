<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use App\Services\ShopSubscriptionService;
use App\Services\XenditGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class XenditWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        XenditGateway $gateway,
        PaymentService $payments,
        ShopSubscriptionService $subscriptions
    ): JsonResponse {
        try {
            $payload = $gateway->verifyWebhook(
                $request->getContent(),
                $request->header('x-callback-token')
            );
        } catch (RuntimeException $exception) {
            Log::warning('Xendit webhook rejected', [
                'reason' => $exception->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => $exception->getMessage()], 401);
        }

        $reference = data_get($payload, 'data.reference_id');
        $isSubscription = data_get($payload, 'data.metadata.purpose') === 'shop_subscription'
            || (is_string($reference) && str_starts_with($reference, 'SC-SUB-'));

        $result = $isSubscription
            ? $subscriptions->processWebhook($payload)
            : $payments->processWebhook($payload);

        return response()->json($result, 200);
    }
}
