<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:100'],
            'shop' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $logs = ActivityLog::query()
            ->with(['user', 'shop'])
            ->when($filters['action'] ?? null, fn (Builder $query, $action) => $query->where('action', 'like', $action.'%'))
            ->when($filters['shop'] ?? null, fn (Builder $query, $shop) => $query->where('shop_id', $shop))
            ->when($filters['q'] ?? null, fn (Builder $query, $term) => $query->where('description', 'like', '%'.trim($term).'%'))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $actions = ActivityLog::query()->distinct()->orderBy('action')->pluck('action');
        $shops = Shop::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.activity.index', compact('logs', 'filters', 'actions', 'shops'));
    }
}
