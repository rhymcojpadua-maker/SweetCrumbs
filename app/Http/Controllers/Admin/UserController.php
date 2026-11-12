<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $users = User::query()
            ->with('shop')
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($filters['q'] ?? null, function ($query, $term) {
                $term = '%'.trim($term).'%';
                $query->where(fn ($builder) => $builder
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => UserRole::cases(),
        ]);
    }

    public function updateStatus(Request $request, User $user, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:activate,deactivate'],
        ]);

        if ((int) $user->id === (int) $request->user()->id) {
            return back()->with('error', 'You cannot deactivate your own administrator account.');
        }

        $active = $data['action'] === 'activate';
        $user->update(['is_active' => $active]);

        $logger->log(
            $active ? 'user.activated' : 'user.deactivated',
            'User '.$user->email.' '.($active ? 'activated' : 'deactivated'),
            $user
        );

        return back()->with('success', 'User account '.($active ? 'activated' : 'deactivated').'.');
    }
}
