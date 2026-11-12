<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function show(Request $request, string $order): View
    {
        $order = Order::query()
            ->with(['items', 'payment', 'shop', 'customer'])
            ->where('order_number', $order)
            ->firstOrFail();

        $this->authorizeView($request, $order);

        return view('public.order', [
            'order' => $order,
            'steps' => $this->progressSteps($order),
            'canCancel' => $order->isCancellableByCustomer() && ! $order->isPaid(),
            'canPay' => $order->status === OrderStatus::PendingPayment && ! $order->isPaid(),
        ]);
    }

    public function trackForm(): View
    {
        return view('public.track');
    }

    public function track(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:190'],
        ]);

        $order = Order::query()
            ->where('order_number', strtoupper(trim($data['order_number'])))
            ->whereRaw('LOWER(customer_email) = ?', [strtolower(trim($data['email']))])
            ->first();

        if (! $order) {
            return back()->withInput()->withErrors([
                'order_number' => 'We could not find an order with that number and email address.',
            ]);
        }

        return redirect()->to($order->publicUrl());
    }

    public function cancel(Request $request, Order $order, OrderStatusService $statusService): RedirectResponse
    {
        $this->authorizeView($request, $order);

        if (! $order->isCancellableByCustomer()) {
            return back()->with('error', 'This order can no longer be cancelled online. Please contact the bakery.');
        }

        if ($order->isPaid()) {
            return back()->with('error', 'Paid orders need a refund before cancellation. Please contact the bakery.');
        }

        try {
            $statusService->transition($order, OrderStatus::Cancelled, $request->user(), 'Cancelled by customer');
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->to($order->fresh()->publicUrl())->with('success', 'Your order has been cancelled.');
    }

    private function authorizeView(Request $request, Order $order): void
    {
        $token = (string) $request->query('token', '');
        $user = $request->user();

        $allowed = ($token !== '' && hash_equals($order->public_token, $token))
            || ($user && ((int) $order->customer_id === (int) $user->id || $user->isSuperAdmin()))
            || ($user && $user->isShopOwner() && (int) $order->shop_id === (int) $user->shop?->id);

        abort_unless($allowed, 404);
    }

    private function progressSteps(Order $order): array
    {
        if (in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Rejected], true)) {
            return [];
        }

        $steps = [
            ['status' => OrderStatus::PendingPayment, 'label' => 'Payment'],
            ['status' => OrderStatus::PendingConfirmation, 'label' => 'Received'],
            ['status' => OrderStatus::Confirmed, 'label' => 'Confirmed'],
            ['status' => OrderStatus::Preparing, 'label' => 'Preparing'],
        ];

        $steps[] = $order->fulfillment_type->value === 'pickup'
            ? ['status' => OrderStatus::ReadyForPickup, 'label' => 'Ready']
            : ['status' => OrderStatus::OutForDelivery, 'label' => 'On the way'];

        $steps[] = ['status' => OrderStatus::Completed, 'label' => 'Completed'];

        $currentIndex = collect($steps)->search(fn ($step) => $step['status'] === $order->status);

        return collect($steps)->map(function ($step, $index) use ($currentIndex) {
            return $step + [
                'state' => $currentIndex === false ? 'upcoming' : ($index < $currentIndex ? 'complete' : ($index === $currentIndex ? 'current' : 'upcoming')),
            ];
        })->all();
    }
}
