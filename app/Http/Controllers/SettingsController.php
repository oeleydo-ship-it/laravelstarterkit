<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingsRequest;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $tenant = currentTenant();
        $settings = Setting::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->pluck('value', 'key');

        $timezones = \DateTimeZone::listIdentifiers();

        return view('settings.index', compact('settings', 'timezones'));
    }

    public function update(SettingsRequest $request)
    {
        $tenant = currentTenant();

        // Save text settings
        $keys = ['company_name', 'timezone', 'notification_email'];
        foreach ($keys as $key) {
            if ($request->has($key)) {
                Setting::set($key, $request->input($key), $tenant->id);
            }
        }

        // Update tenant name if company_name changed
        if ($request->has('company_name')) {
            $tenant->update(['name' => $request->company_name]);
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos/' . $tenant->id, 'public');
            Setting::set('logo', $path, $tenant->id);
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
