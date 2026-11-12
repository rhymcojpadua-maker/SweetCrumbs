<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function edit(Request $request): View
    {
        $shop = $request->user()->shop;

        return view('dashboard.storefront.edit', compact('shop'));
    }

    public function update(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $shop = $request->user()->shop;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'description' => ['required', 'string', 'min:40', 'max:3000'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        if ($request->hasFile('logo')) {
            if ($shop->logo_path) {
                Storage::disk('public')->delete($shop->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('shops/'.$shop->id, 'public');
        }

        if ($request->hasFile('cover')) {
            if ($shop->cover_path) {
                Storage::disk('public')->delete($shop->cover_path);
            }
            $data['cover_path'] = $request->file('cover')->store('shops/'.$shop->id, 'public');
        }

        unset($data['logo'], $data['cover']);
        $shop->update($data);

        $logger->log('shop.storefront_updated', 'Storefront details updated', $shop, $shop);

        return back()->with('success', 'Your storefront has been updated.');
    }
}
