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
                'app_logo' => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',
            ]);

            SystemSetting::set('app_name', $request->input('app_name'), 'general');
            SystemSetting::set('support_email', $request->input('support_email'), 'general');
            SystemSetting::set('footer_text', $request->input('footer_text'), 'general');

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
            ]);

            SystemSetting::set('stripe_key', $request->input('stripe_key'), 'stripe');
            SystemSetting::set('stripe_secret', $request->input('stripe_secret'), 'stripe');
            SystemSetting::set('stripe_webhook_secret', $request->input('stripe_webhook_secret'), 'stripe');
        }

        return redirect()->route('superadmin.settings')
            ->with('success', ucfirst($tab) . ' settings updated successfully.');
    }
}
