<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ShopStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ShopRegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.shop-register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'shop_name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'description' => ['required', 'string', 'min:40', 'max:2000'],
            'shop_email' => ['required', 'email', 'max:190'],
            'shop_phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        [$user, $shop] = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'phone' => $data['phone'],
                'role' => UserRole::ShopOwner,
                'password' => $data['password'],
            ]);

            $slug = $this->uniqueSlug($data['shop_name']);

            $shop = Shop::create([
                'owner_id' => $user->id,
                'name' => $data['shop_name'],
                'slug' => $slug,
                'tagline' => $data['tagline'] ?? null,
                'description' => $data['description'],
                'email' => strtolower($data['shop_email']),
                'phone' => $data['shop_phone'],
                'address' => $data['address'],
                'city' => $data['city'],
                'province' => $data['province'],
                'postal_code' => $data['postal_code'] ?? null,
                'status' => ShopStatus::Pending,
            ]);

            User::query()->where('role', UserRole::SuperAdmin->value)->get()->each(function (User $admin) use ($shop) {
                $admin->appNotifications()->create([
                    'shop_id' => $shop->id,
                    'type' => 'shop_application',
                    'title' => 'New bakery application',
                    'body' => $shop->name.' applied to join SweetCrumbs.',
                    'data' => ['shop_id' => $shop->id],
                ]);
            });

            return [$user, $shop];
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard.pending')->with('success', 'Your bakery application was submitted for review.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'bakery';
        $slug = $base;
        $counter = 2;

        while (Shop::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
