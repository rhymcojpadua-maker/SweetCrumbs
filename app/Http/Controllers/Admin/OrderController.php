<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'shop' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $orders = Order::query()
            ->with('shop')
            ->withCount('items')
            ->when($filters['status'] ?? null, fn (Builder $query, $status) => $query->where('status', $status))
            ->when($filters['payment_status'] ?? null, fn (Builder $query, $status) => $query->where('payment_status', $status))
            ->when($filters['shop'] ?? null, fn (Builder $query, $shop) => $query->where('shop_id', $shop))
            ->when($filters['q'] ?? null, function (Builder $query, $term) {
                $term = '%'.trim($term).'%';
                $query->where(fn (Builder $builder) => $builder
                    ->where('order_number', 'like', $term)
                    ->orWhere('customer_name', 'like', $term)
                    ->orWhere('customer_email', 'like', $term));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => $filters,
            'statuses' => OrderStatus::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['items', 'payment.transactions', 'shop', 'customer']);

        return view('admin.orders.show', compact('order'));
    }
}
