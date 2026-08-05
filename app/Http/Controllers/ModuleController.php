<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\TenantModule;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function index()
    {
        $tenant = currentTenant();
        $modules = Module::all();
        $tenantModules = $tenant->tenantModules()->pluck('enabled', 'module_key');

        return view('modules.index', compact('modules', 'tenantModules'));
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'module_key' => 'required|string|exists:modules,key',
            'enabled' => 'required|boolean',
        ]);

        $tenant = currentTenant();

        // Check plan limits for max_modules when enabling
        if ($request->enabled) {
            $maxModules = $tenant->getPlanLimit('max_modules', -1);
            if ($maxModules > 0) {
                $currentEnabled = $tenant->tenantModules()->where('enabled', true)->count();
                if ($currentEnabled >= $maxModules) {
                    return redirect()->back()->with('error', "You can only enable {$maxModules} modules on your current plan. Please upgrade.");
                }
            }
        }

        TenantModule::updateOrCreate(
            ['tenant_id' => $tenant->id, 'module_key' => $request->module_key],
            ['enabled' => $request->enabled]
        );

        $action = $request->enabled ? 'enabled' : 'disabled';

        return redirect()->back()->with('success', "Module {$action} successfully.");
    }
}
