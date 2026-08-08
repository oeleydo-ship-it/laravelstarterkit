<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Support\ModuleCatalog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $tenants = Tenant::with('plan')->withCount('users')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->latest()->paginate(20)->withQueryString();
        return view('superadmin.tenants.index', compact('tenants'));
    }

    public function edit(Tenant $tenant)
    {
        ModuleCatalog::sync();
        $tenant->load('tenantModules');
        return view('superadmin.tenants.edit', [
            'tenant' => $tenant,
            'plans' => Plan::orderBy('sort_order')->get(),
            'modules' => Module::orderBy('name')->get(),
            'enabledModules' => $tenant->tenantModules->where('enabled', true)->pluck('module_key')->all(),
        ]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('tenants')->ignore($tenant)],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'trial_ends_at' => ['nullable', 'date'],
            'modules' => ['array'],
            'modules.*' => ['string', 'exists:modules,key'],
        ]);
        $tenant->update(collect($data)->only(['name', 'slug', 'plan_id', 'trial_ends_at'])->all());
        $enabled = $data['modules'] ?? [];
        foreach (Module::pluck('key') as $key) {
            TenantModule::updateOrCreate(['tenant_id' => $tenant->id, 'module_key' => $key], ['enabled' => in_array($key, $enabled, true)]);
        }
        return back()->with('success', 'Workspace and module controls updated successfully.');
    }
}
