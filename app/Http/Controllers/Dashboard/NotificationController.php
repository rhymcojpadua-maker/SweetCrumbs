<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->appNotifications()
            ->latest()
            ->paginate(20);

        return view('dashboard.notifications.index', compact('notifications'));
    }

    public function read(Request $request, AppNotification $notification): RedirectResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 404);

        $notification->update(['read_at' => $notification->read_at ?? now()]);

        if ($notification->data['order_id'] ?? null) {
            $order = Order::query()->find($notification->data['order_id']);
            if ($order && (int) $order->shop_id === (int) $request->user()->shop?->id) {
                return redirect()->route('dashboard.orders.show', $order);
            }
        }

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->appNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
