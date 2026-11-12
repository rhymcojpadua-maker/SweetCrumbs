<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShopStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\View as ViewResponse;

class AdminDashboardController extends Controller
{
    public function index(): ViewResponse
    {
        $periodStart = now()->subDays(29)->startOfDay();
        $previousStart = now()->subDays(59)->startOfDay();
        $previousEnd = now()->subDays(30)->endOfDay();

        $revenue = (float) Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Rejected->value])
            ->whereBetween('placed_at', [$periodStart, now()])
            ->sum('total');

        $previousRevenue = (float) Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Rejected->value])
            ->whereBetween('placed_at', [$previousStart, $previousEnd])
            ->sum('total');

        $orders = Order::query()
            ->with(['shop'])
            ->latest()
            ->take(8)
            ->get();

        $pendingShops = Shop::query()
            ->with('owner')
            ->where('status', ShopStatus::Pending->value)
            ->oldest()
            ->take(6)
            ->get();

        $topShops = Shop::query()
            ->withSum(['orders as paid_revenue' => fn ($query) => $query->where('payment_status', PaymentStatus::Paid->value)], 'total')
            ->withCount('orders')
            ->orderByDesc('paid_revenue')
            ->take(6)
            ->get();

        $daily = $this->dailySeries(14);

        $statusCounts = Order::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $metrics = [
            'revenue' => $revenue,
            'revenue_change' => $this->change($revenue, $previousRevenue),
            'orders_today' => Order::query()->whereDate('placed_at', today())->count(),
            'active_shops' => Shop::query()->where('status', ShopStatus::Active->value)->count(),
            'pending_shops' => Shop::query()->where('status', ShopStatus::Pending->value)->count(),
            'customers' => User::query()->where('role', 'customer')->count(),
            'pending_payments' => Payment::query()->where('status', PaymentStatus::Pending->value)->count(),
        ];

        return view('admin.dashboard', compact('metrics', 'orders', 'pendingShops', 'topShops', 'daily', 'statusCounts'));
    }

    private function change(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function dailySeries(int $days): Collection
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $orders = Order::query()->where('placed_at', '>=', $start)->get(['placed_at', 'total', 'payment_status', 'status']);

        return collect(range(0, $days - 1))->map(function ($offset) use ($start, $orders) {
            $date = $start->copy()->addDays($offset);
            $dayOrders = $orders->filter(fn ($order) => $order->placed_at?->isSameDay($date));
            $revenue = $dayOrders->filter(fn ($order) => $order->payment_status === PaymentStatus::Paid
                && ! in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Rejected], true))
                ->sum(fn ($order) => (float) $order->total);

            return [
                'label' => $date->format('M j'),
                'orders' => $dayOrders->count(),
                'revenue' => round((float) $revenue, 2),
            ];
        });
    }
}
