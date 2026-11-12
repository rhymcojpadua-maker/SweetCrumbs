<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use App\Services\PayMongoGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PayMongoWebhookController extends Controller
{
    public function __invoke(Request $request, PayMongoGateway $gateway, PaymentService $payments): JsonResponse
    {
        try {
            $payload = $gateway->verifyWebhook(
                $request->getContent(),
                $request->header('Paymongo-Signature')
            );
        } catch (RuntimeException $exception) {
            Log::warning('PayMongo webhook rejected', ['reason' => $exception->getMessage(), 'ip' => $request->ip()]);

            return response()->json(['message' => $exception->getMessage()], 401);
        }

        $result = $payments->processWebhook($payload);

        return response()->json($result, 200);
    }
}
