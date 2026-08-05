<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        $tenant = currentTenant();

        if (!$tenant) {
            abort(403, 'No tenant context.');
        }

        if (!$tenant->isModuleEnabled($moduleKey)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'This module is not enabled for your workspace.'], 403);
            }

            return redirect()->route('dashboard')
                ->with('error', 'The "' . $moduleKey . '" module is not enabled. Please enable it in Modules settings.');
        }

        $user = $request->user();
        if ($user && ! $user->canAccessModule($moduleKey)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You do not have access to this module.'], 403);
            }

            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to access this module.');
        }

        return $next($request);
    }
}
