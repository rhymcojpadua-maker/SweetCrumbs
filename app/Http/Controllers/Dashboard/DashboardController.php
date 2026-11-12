<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShopStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $shop = $request->user()->shop()->with('settings')->firstOrFail();

        $periodStart = now()->subDays(29)->startOfDay();
        $periodEnd = now()->endOfDay();
        $previousStart = now()->subDays(59)->startOfDay();
        $previousEnd = now()->subDays(30)->endOfDay();

        $revenue = $this->revenue($shop, $periodStart, $periodEnd);
        $previousRevenue = $this->revenue($shop, $previousStart, $previousEnd);
        $orderCount = $this->orderCount($shop, $periodStart, $periodEnd);
        $previousOrderCount = $this->orderCount($shop, $previousStart, $previousEnd);

        $pendingConfirmation = Order::query()
            ->where('shop_id', $shop->id)
            ->where('status', OrderStatus::PendingConfirmation->value)
            ->count();

        $todayOrders = Order::query()
            ->where('shop_id', $shop->id)
            ->whereDate('placed_at', today())
            ->count();

        $lowStock = Product::query()
            ->where('shop_id', $shop->id)
            ->where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->take(6)
            ->get();

        $upcoming = Order::query()
            ->with('items')
            ->where('shop_id', $shop->id)
            ->whereDate('scheduled_date', '>=', today())
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Rejected->value])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->take(6)
            ->get();

        $recentOrders = Order::query()
            ->withCount('items')
            ->where('shop_id', $shop->id)
            ->latest()
            ->take(8)
            ->get();

        $topProducts = OrderItem::query()
            ->selectRaw('product_name, SUM(quantity) as units, SUM(line_total) as revenue')
            ->whereHas('order', fn ($query) => $query->where('shop_id', $shop->id)
                ->where('payment_status', PaymentStatus::Paid->value)
                ->whereBetween('placed_at', [$periodStart, $periodEnd]))
            ->groupBy('product_name')
            ->orderByDesc('units')
            ->take(5)
            ->get();

        $daily = $this->dailySeries($shop, 14);
        $statusCounts = Order::query()
            ->where('shop_id', $shop->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $shop->loadCount(['products', 'orders']);

        return view('dashboard.index', [
            'shop' => $shop,
            'revenue' => $revenue,
            'previousRevenue' => $previousRevenue,
            'revenueChange' => $this->change($revenue, $previousRevenue),
            'orderCount' => $orderCount,
            'orderChange' => $this->change($orderCount, $previousOrderCount),
            'pendingConfirmation' => $pendingConfirmation,
            'todayOrders' => $todayOrders,
            'lowStock' => $lowStock,
            'upcoming' => $upcoming,
            'recentOrders' => $recentOrders,
            'topProducts' => $topProducts,
            'daily' => $daily,
            'statusCounts' => $statusCounts,
        ]);
    }

    public function pending(Request $request): View
    {
        $shop = $request->user()->shop()->with('settings')->first();

        if ($shop?->status === ShopStatus::Active) {
            return redirect()->route('dashboard.index');
        }

        return view('dashboard.pending', compact('shop'));
    }

    public function suspended(Request $request): View
    {
        $shop = $request->user()->shop;

        if (! $shop || $shop->status !== ShopStatus::Suspended) {
            return redirect()->route('dashboard.index');
        }

        return view('dashboard.suspended', compact('shop'));
    }

    private function revenue(Shop $shop, Carbon $start, Carbon $end): float
    {
        return (float) Order::query()
            ->where('shop_id', $shop->id)
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereBetween('placed_at', [$start, $end])
            ->sum('total');
    }

    private function orderCount(Shop $shop, Carbon $start, Carbon $end): int
    {
        return Order::query()
            ->where('shop_id', $shop->id)
            ->whereBetween('placed_at', [$start, $end])
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Rejected->value])
            ->count();
    }

    private function change(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function dailySeries(Shop $shop, int $days): Collection
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $orders = Order::query()
            ->where('shop_id', $shop->id)
            ->where('placed_at', '>=', $start)
            ->get(['placed_at', 'total', 'payment_status', 'status']);

        return collect(range(0, $days - 1))->map(function ($offset) use ($start, $orders) {
            $date = $start->copy()->addDays($offset);
            $dayOrders = $orders->filter(fn ($order) => $order->placed_at?->isSameDay($date));
            $dayRevenue = $dayOrders
                ->filter(fn ($order) => $order->payment_status === PaymentStatus::Paid
                    && ! in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Rejected], true))
                ->sum(fn ($order) => (float) $order->total);

            return [
                'label' => $date->format('M j'),
                'orders' => $dayOrders->count(),
                'revenue' => round((float) $dayRevenue, 2),
            ];
        });
    }
}
