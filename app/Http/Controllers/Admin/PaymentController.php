<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $payments = Payment::query()
            ->with(['order', 'shop'])
            ->when($filters['status'] ?? null, fn (Builder $query, $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, function (Builder $query, $term) {
                $term = '%'.trim($term).'%';
                $query->where(fn (Builder $builder) => $builder
                    ->where('reference_number', 'like', $term)
                    ->orWhere('provider_payment_id', 'like', $term)
                    ->orWhereHas('shop', fn (Builder $shop) => $shop->where('name', 'like', $term)));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $summary = Payment::query()
            ->selectRaw('status, count(*) as total, sum(amount) as amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return view('admin.payments.index', [
            'payments' => $payments,
            'summary' => $summary,
            'filters' => $filters,
            'statuses' => PaymentStatus::cases(),
        ]);
    }
}
