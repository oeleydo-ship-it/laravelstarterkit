<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Support\Privileges;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OnboardingController extends Controller
{
    public function show()
    {
        if (auth()->user()->tenant_id) {
            return redirect()->route('dashboard');
        }

        return view('onboarding');
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:tenants,slug|alpha_dash',
        ]);

        $user = auth()->user();

        // Create tenant
        $tenant = Tenant::create([
            'name' => $request->company_name,
            'slug' => Str::slug($request->slug),
        ]);

        // Assign user as owner
        $user->update([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'privileges' => Privileges::defaultsForRole('owner'),
        ]);

        // Enable default modules
        $modules = Module::where('enabled_by_default', true)->get();
        foreach ($modules as $module) {
            TenantModule::create([
                'tenant_id' => $tenant->id,
                'module_key' => $module->key,
                'enabled' => true,
            ]);
        }

        // Also add disabled modules so they show up in UI
        $disabledModules = Module::where('enabled_by_default', false)->get();
        foreach ($disabledModules as $module) {
            TenantModule::create([
                'tenant_id' => $tenant->id,
                'module_key' => $module->key,
                'enabled' => false,
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Welcome! Your workspace has been created.');
    }
}
