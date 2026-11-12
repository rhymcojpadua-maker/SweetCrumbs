<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $shop = $request->user()->shop;
        $data = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:to'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($data['from'] ?? now()->subDays(29)->toDateString())->startOfDay();
        $to = Carbon::parse($data['to'] ?? now()->toDateString())->endOfDay();

        $orders = Order::query()
            ->where('shop_id', $shop->id)
            ->whereBetween('placed_at', [$from, $to])
            ->get();

        $validOrders = $orders->reject(fn ($order) => in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Rejected], true));
        $paidOrders = $validOrders->filter(fn ($order) => $order->payment_status === PaymentStatus::Paid);

        $revenue = (float) $paidOrders->sum(fn ($order) => (float) $order->total);
        $averageOrder = $paidOrders->count() > 0 ? $revenue / $paidOrders->count() : 0.0;

        $daily = collect(range(0, max(0, (int) $from->diffInDays($to))))->map(function ($offset) use ($from, $orders) {
            $date = $from->copy()->addDays($offset);
            $dayOrders = $orders->filter(fn ($order) => $order->placed_at?->isSameDay($date));
            $dayPaid = $dayOrders->filter(fn ($order) => $order->payment_status === PaymentStatus::Paid
                && ! in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Rejected], true));

            return [
                'label' => $date->format('M j'),
                'orders' => $dayOrders->count(),
                'revenue' => round((float) $dayPaid->sum(fn ($order) => (float) $order->total), 2),
            ];
        });

        $topProducts = OrderItem::query()
            ->selectRaw('product_name, SUM(quantity) as units, SUM(line_total) as revenue')
            ->whereHas('order', fn ($query) => $query->where('shop_id', $shop->id)
                ->whereBetween('placed_at', [$from, $to])
                ->where('payment_status', PaymentStatus::Paid->value)
                ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Rejected->value]))
            ->groupBy('product_name')
            ->orderByDesc('revenue')
            ->take(8)
            ->get();

        $categories = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('orders.shop_id', $shop->id)
            ->whereBetween('orders.placed_at', [$from, $to])
            ->where('orders.payment_status', PaymentStatus::Paid->value)
            ->whereNotIn('orders.status', [OrderStatus::Cancelled->value, OrderStatus::Rejected->value])
            ->selectRaw("COALESCE(categories.name, 'Uncategorised') as category, SUM(order_items.line_total) as revenue, SUM(order_items.quantity) as units")
            ->groupBy('category')
            ->orderByDesc('revenue')
            ->get();

        $fulfillment = $validOrders->groupBy(fn ($order) => $order->fulfillment_type->value)
            ->map(fn ($group) => $group->count());

        $statuses = $orders->groupBy(fn ($order) => $order->status->value)
            ->map(fn ($group) => $group->count());

        return view('dashboard.reports.index', [
            'shop' => $shop,
            'from' => $from,
            'to' => $to,
            'revenue' => $revenue,
            'averageOrder' => $averageOrder,
            'orderCount' => $validOrders->count(),
            'paidCount' => $paidOrders->count(),
            'daily' => $daily,
            'topProducts' => $topProducts,
            'categories' => $categories,
            'fulfillment' => $fulfillment,
            'statuses' => $statuses,
        ]);
    }
}
