<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        $general = SystemSetting::group('general');
        $stripe = SystemSetting::group('stripe');

        return view('superadmin.settings', compact('general', 'stripe'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'tab' => 'required|in:general,stripe',
        ]);

        $tab = $request->input('tab');

        if ($tab === 'general') {
            $request->validate([
                'app_name' => 'required|string|max:255',
                'support_email' => 'nullable|email|max:255',
                'footer_text' => 'nullable|string|max:500',
                'timezone' => 'required|timezone',
                'currency' => 'required|string|size:3',
                'allow_registration' => 'nullable|boolean',
                'app_logo' => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',
            ]);

            SystemSetting::set('app_name', $request->input('app_name'), 'general');
            SystemSetting::set('support_email', $request->input('support_email'), 'general');
            SystemSetting::set('footer_text', $request->input('footer_text'), 'general');
            SystemSetting::set('timezone', $request->input('timezone'), 'general');
            SystemSetting::set('currency', strtoupper($request->input('currency')), 'general');
            SystemSetting::set('allow_registration', $request->boolean('allow_registration') ? '1' : '0', 'general');

            if ($request->hasFile('app_logo')) {
                // Delete old logo if exists
                $oldLogo = SystemSetting::get('app_logo');
                if ($oldLogo) {
                    Storage::disk('public')->delete($oldLogo);
                }

                $path = $request->file('app_logo')->store('branding', 'public');
                SystemSetting::set('app_logo', $path, 'general');
            }

            // Update config at runtime
            config(['app.name' => $request->input('app_name')]);
        }

        if ($tab === 'stripe') {
            $request->validate([
                'stripe_key' => 'nullable|string|max:255',
                'stripe_secret' => 'nullable|string|max:255',
                'stripe_webhook_secret' => 'nullable|string|max:255',
                'stripe_mode' => 'required|in:test,live',
                'billing_currency' => 'required|string|size:3',
            ]);

            SystemSetting::set('stripe_key', $request->input('stripe_key'), 'stripe');
            if ($request->filled('stripe_secret')) SystemSetting::set('stripe_secret', $request->input('stripe_secret'), 'stripe');
            if ($request->filled('stripe_webhook_secret')) SystemSetting::set('stripe_webhook_secret', $request->input('stripe_webhook_secret'), 'stripe');
            SystemSetting::set('stripe_mode', $request->input('stripe_mode'), 'stripe');
            SystemSetting::set('billing_currency', strtoupper($request->input('billing_currency')), 'stripe');
        }

        return redirect()->route('superadmin.settings')
            ->with('success', ucfirst($tab) . ' settings updated successfully.');
    }
}
