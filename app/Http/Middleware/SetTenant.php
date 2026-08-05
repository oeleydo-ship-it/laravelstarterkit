<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Superadmin can bypass tenant context
        if ($user->is_superadmin) {
            return $next($request);
        }

        // If user has no tenant, redirect to onboarding
        if (!$user->tenant_id) {
            if (!$request->routeIs('onboarding.*')) {
                return redirect()->route('onboarding.show');
            }
            return $next($request);
        }

        // Load the tenant and bind to container
        $tenant = $user->tenant()->with('plan')->first();

        if (!$tenant) {
            abort(403, 'Tenant not found.');
        }

        app()->instance('tenant', $tenant);

        // Share tenant with all views
        view()->share('currentTenant', $tenant);
        view()->share('currentUser', $user);

        return $next($request);
    }
}
