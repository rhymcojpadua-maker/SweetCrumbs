<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use App\Services\XenditGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class XenditWebhookController extends Controller
{
    public function __invoke(Request $request, XenditGateway $gateway, PaymentService $payments): JsonResponse
    {
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

        return response()->json($payments->processWebhook($payload), 200);
    }
}
