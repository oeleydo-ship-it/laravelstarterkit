<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromSlug
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Tenant::where('slug', $request->route('tenantSlug'))->first();

        if (! $tenant) {
            abort(404);
        }

        if (! $tenant->isModuleEnabled('chat')) {
            abort(403, 'Live chat is not enabled for this workspace.');
        }

        app()->instance('tenant', $tenant);
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
