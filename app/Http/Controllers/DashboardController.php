<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\TenantModule;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->user()->is_superadmin) {
            return redirect()->route('superadmin.dashboard');
        }

        $tenant = currentTenant();

        $totalUsers = $tenant->users()->where('status', 'active')->count();
        $activeModules = $tenant->tenantModules()->where('enabled', true)->count();
        $recentActivity = ActivityLog::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('dashboard', compact('totalUsers', 'activeModules', 'recentActivity'));
    }
}
