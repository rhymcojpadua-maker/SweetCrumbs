<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\OrderStatusService;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ShopOrderController extends Controller
{
    public function index(Request $request): View
    {
        $shop = $request->user()->shop;

        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'q' => ['nullable', 'string', 'max:100'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $orders = Order::query()
            ->withCount('items')
            ->where('shop_id', $shop->id)
            ->when($filters['status'] ?? null, fn (Builder $query, $status) => $query->where('status', $status))
            ->when($filters['payment_status'] ?? null, fn (Builder $query, $status) => $query->where('payment_status', $status))
            ->when($filters['date'] ?? null, fn (Builder $query, $date) => $query->whereDate('scheduled_date', $date))
            ->when($filters['q'] ?? null, function (Builder $query, $term) {
                $term = '%'.trim($term).'%';
                $query->where(fn (Builder $builder) => $builder
                    ->where('order_number', 'like', $term)
                    ->orWhere('customer_name', 'like', $term)
                    ->orWhere('customer_email', 'like', $term)
                    ->orWhere('customer_phone', 'like', $term));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('dashboard.orders.index', [
            'orders' => $orders,
            'filters' => $filters,
            'statuses' => OrderStatus::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $shop = $request->user()->shop;
        abort_unless((int) $order->shop_id === (int) $shop->id, 404);

        $order->load(['items', 'payment.transactions', 'customer']);

        $transactions = PaymentTransaction::query()
            ->where('order_id', $order->id)
            ->latest()
            ->get();

        $allowed = collect($order->status->allowedTransitions())->mapWithKeys(fn ($status) => [$status->value => $status]);
        if ($order->isPaid()) {
            $allowed = $allowed->except([OrderStatus::Cancelled->value, OrderStatus::Rejected->value]);
        }
        if ($order->fulfillment_type->value === 'pickup') {
            $allowed = $allowed->except([OrderStatus::OutForDelivery->value]);
        } else {
            $allowed = $allowed->except([OrderStatus::ReadyForPickup->value]);
        }

        return view('dashboard.orders.show', [
            'order' => $order,
            'allowedStatuses' => $allowed,
            'transactions' => $transactions,
        ]);
    }

    public function updateStatus(Request $request, Order $order, OrderStatusService $statusService): RedirectResponse
    {
        $shop = $request->user()->shop;
        abort_unless((int) $order->shop_id === (int) $shop->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $statusService->transition($order, OrderStatus::from($data['status']), $request->user(), $data['note'] ?? null);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'Order '.$order->order_number.' updated.');
    }

    public function markCashReceived(Request $request, Order $order, PaymentService $paymentService): RedirectResponse
    {
        $shop = $request->user()->shop;
        abort_unless((int) $order->shop_id === (int) $shop->id, 404);

        try {
            $paymentService->markCashOnDeliveryPaid($order);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'Cash payment for order '.$order->order_number.' recorded.');
    }
}
