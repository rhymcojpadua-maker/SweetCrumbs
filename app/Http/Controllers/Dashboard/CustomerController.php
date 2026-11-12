<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ShopCustomer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $shop = $request->user()->shop;
        $search = $request->string('q')->trim()->toString();

        $customers = ShopCustomer::query()
            ->where('shop_id', $shop->id)
            ->when($search !== '', fn ($query) => $query->where(fn ($builder) => $builder
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('phone', 'like', '%'.$search.'%')))
            ->orderByDesc('last_order_at')
            ->paginate(15)
            ->withQueryString();

        return view('dashboard.customers.index', compact('shop', 'customers', 'search'));
    }

    public function show(Request $request, ShopCustomer $customer): View
    {
        $shop = $request->user()->shop;
        abort_unless((int) $customer->shop_id === (int) $shop->id, 404);

        $orders = Order::query()
            ->withCount('items')
            ->where('shop_id', $shop->id)
            ->whereRaw('LOWER(customer_email) = ?', [strtolower($customer->email)])
            ->latest()
            ->paginate(10);

        return view('dashboard.customers.show', compact('shop', 'customer', 'orders'));
    }
}
