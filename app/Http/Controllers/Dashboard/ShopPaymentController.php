<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShopPaymentController extends Controller
{
    public function index(Request $request): View
    {
        $shop = $request->user()->shop;

        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $payments = Payment::query()
            ->with(['order'])
            ->where('shop_id', $shop->id)
            ->when($filters['status'] ?? null, fn (Builder $query, $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, function (Builder $query, $term) {
                $term = '%'.trim($term).'%';
                $query->where(fn (Builder $builder) => $builder
                    ->where('reference_number', 'like', $term)
                    ->orWhere('provider_payment_id', 'like', $term)
                    ->orWhereHas('order', fn (Builder $order) => $order->where('customer_name', 'like', $term)->orWhere('order_number', 'like', $term)));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $summary = Payment::query()
            ->where('shop_id', $shop->id)
            ->selectRaw('status, count(*) as total, sum(amount) as amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return view('dashboard.payments.index', [
            'shop' => $shop,
            'payments' => $payments,
            'summary' => $summary,
            'filters' => $filters,
            'statuses' => PaymentStatus::cases(),
        ]);
    }
}
