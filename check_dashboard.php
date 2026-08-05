<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Simulate logging in as owner
$user = App\Models\User::withoutGlobalScopes()->where('email', 'owner@demo.com')->first();
if (!$user) {
    echo "No owner user found\n";
    exit;
}

echo "User: " . $user->email . "\n";
echo "tenant_id: " . $user->tenant_id . "\n";
echo "role: " . $user->role . "\n";

// Simulate SetTenant middleware
if ($user->tenant_id) {
    try {
        $tenant = $user->tenant()->with('plan')->first();
        echo "Tenant: " . ($tenant ? $tenant->name : 'NULL') . "\n";
        if ($tenant) {
            $app->instance('tenant', $tenant);
            echo "Plan: " . ($tenant->plan ? $tenant->plan->name : 'NULL') . "\n";
        }
    } catch (Exception $e) {
        echo "SetTenant error: " . $e->getMessage() . "\n";
    }
}

// Simulate DashboardController
try {
    $tenant = currentTenant();
    echo "\ncurrentTenant(): " . ($tenant ? $tenant->name : 'NULL') . "\n";

    $totalUsers = $tenant->users()->where('status', 'active')->count();
    echo "totalUsers: " . $totalUsers . "\n";

    $activeModules = $tenant->tenantModules()->where('enabled', true)->count();
    echo "activeModules: " . $activeModules . "\n";

    $recentActivity = App\Models\ActivityLog::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->with('user')
        ->orderByDesc('created_at')
        ->limit(10)
        ->get();
    echo "recentActivity count: " . $recentActivity->count() . "\n";

    echo "\nDashboard would load successfully!\n";
} catch (Exception $e) {
    echo "Dashboard error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
