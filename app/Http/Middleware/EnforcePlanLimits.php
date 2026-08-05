<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePlanLimits
{
    public function handle(Request $request, Closure $next, string $limitKey): Response
    {
        $tenant = currentTenant();

        if (!$tenant || !$tenant->plan) {
            return $next($request);
        }

        $limit = $tenant->getPlanLimit($limitKey);

        if (is_null($limit)) {
            return $next($request);
        }

        $exceeded = false;
        $message = 'You have reached your plan limit.';

        switch ($limitKey) {
            case 'max_users':
                $currentCount = $tenant->activeUserCount();
                if ($currentCount >= $limit) {
                    $exceeded = true;
                    $message = "You have reached the maximum of {$limit} users on your current plan. Please upgrade to add more users.";
                }
                break;

            case 'max_modules':
                $enabledCount = $tenant->tenantModules()->where('enabled', true)->count();
                if ($enabledCount >= $limit) {
                    $exceeded = true;
                    $message = "You have reached the maximum of {$limit} modules on your current plan. Please upgrade to enable more modules.";
                }
                break;

            default:
                // Generic numeric limit check
                break;
        }

        if ($exceeded) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return redirect()->back()->with('error', $message);
        }

        return $next($request);
    }
}
