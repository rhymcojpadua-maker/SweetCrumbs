<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function resume(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        if ($order->isPaid()) {
            return redirect()->to($order->publicUrl())->with('success', 'This order is already paid.');
        }

        if ($order->status !== OrderStatus::PendingPayment) {
            return redirect()->to($order->publicUrl())->with('error', 'Payment can no longer be started for this order.');
        }

        $payment = $order->payment;

        try {
            if ($payment->provider === 'xendit' && $payment->checkout_url && $payment->status->value === 'pending') {
                return redirect()->away($payment->checkout_url);
            }

            $this->paymentService->startCheckout($order->load('items', 'shop'), $payment);

            return redirect()->away($payment->fresh()->checkout_url);
        } catch (RuntimeException|ConnectionException $exception) {
            return redirect()->to($order->publicUrl())
                ->with('error', 'We could not connect to Xendit right now. Check your order status before trying payment again.');
        }
    }

    public function return(Request $request, string $order): RedirectResponse
    {
        $order = Order::query()->where('order_number', $order)->firstOrFail();
        $this->authorizeOrder($request, $order);

        if ($order->payment && ! $order->isPaid()) {
            $this->paymentService->syncFromProvider($order->payment);
            $order->refresh();
        }

        if ($order->isPaid()) {
            return redirect()->to($order->publicUrl())->with('success', 'Payment confirmed. Your bakery has received your order.');
        }

        return redirect()->to($order->publicUrl())
            ->with('warning', 'We are still waiting for Xendit to confirm your payment. This page will update as soon as the payment is verified.');
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        $token = (string) $request->query('token', '');
        $user = $request->user();

        $allowed = ($token !== '' && hash_equals($order->public_token, $token))
            || ($user && (int) $order->customer_id === (int) $user->id)
            || ($user && $user->isSuperAdmin())
            || ($user && $user->isShopOwner() && (int) $order->shop_id === (int) $user->shop?->id);

        abort_unless($allowed, 404);
    }
}
