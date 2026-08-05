<?php

namespace App\Http\Middleware;

use App\Models\EngageSite;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve the tenant from an opaque public site key for /x/{siteKey} routes.
 */
class SetTenantFromEngageSiteKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $siteKey = (string) $request->route('siteKey');

        $site = EngageSite::withoutGlobalScopes()
            ->where('public_key', $siteKey)
            ->first();

        abort_if(! $site, 404);

        $tenant = Tenant::query()->find($site->tenant_id);

        abort_if(! $tenant || ! $tenant->isModuleEnabled('engage'), 404);

        app()->instance('tenant', $tenant);
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('engage_site', $site);

        return $next($request);
    }
}
