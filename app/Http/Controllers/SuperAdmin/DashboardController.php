<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'totalTenants' => Tenant::count(),
            'totalUsers' => User::withoutGlobalScopes()->count(),
            'activePlans' => Plan::where('is_active', true)->count(),
            'recentTenants' => Tenant::with('plan')
                ->latest()
                ->take(10)
                ->get(),
            'recentUsers' => User::withoutGlobalScopes()
                ->with('tenant')
                ->latest()
                ->take(10)
                ->get(),
        ];

        return view('superadmin.dashboard', $stats);
    }
}
