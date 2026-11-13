<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $settings = [
            'platform_name' => PlatformSetting::get('platform_name', 'SweetCrumbs'),
            'support_email' => PlatformSetting::get('support_email', config('cakelle.support_email')),
            'support_phone' => PlatformSetting::get('support_phone', config('cakelle.support_phone')),
            'announcement' => PlatformSetting::get('announcement', ''),
            'announcement_enabled' => PlatformSetting::get('announcement_enabled', false),
        ];

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'platform_name' => ['required', 'string', 'max:80'],
            'support_email' => ['required', 'email', 'max:190'],
            'support_phone' => ['required', 'string', 'max:30'],
            'announcement' => ['nullable', 'string', 'max:300'],
            'announcement_enabled' => ['nullable', 'boolean'],
        ]);

        foreach (['platform_name', 'support_email', 'support_phone', 'announcement'] as $key) {
            PlatformSetting::set($key, $data[$key] ?? '');
        }
        PlatformSetting::set('announcement_enabled', $request->boolean('announcement_enabled'));

        $logger->log('settings.updated', 'Platform settings updated');

        return back()->with('success', 'Platform settings saved.');
    }
}
