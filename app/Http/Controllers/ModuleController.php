<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\TenantModule;
use App\Support\ModuleCatalog;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function index()
    {
        // Ensure catalog modules exist even if production never re-ran seeders.
        ModuleCatalog::sync();

        $tenant = currentTenant();
        $modules = Module::query()->orderBy('name')->get();
        $tenantModules = $tenant->tenantModules()->pluck('enabled', 'module_key');

        // Create missing tenant_module rows so toggles have a stable baseline.
        foreach ($modules as $module) {
            if (! $tenantModules->has($module->key)) {
                TenantModule::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'module_key' => $module->key,
                    ],
                    ['enabled' => (bool) $module->enabled_by_default]
                );
            }
        }

        $tenantModules = $tenant->tenantModules()->pluck('enabled', 'module_key');

        return view('modules.index', compact('modules', 'tenantModules'));
    }

    public function toggle(Request $request)
    {
        ModuleCatalog::sync();

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
