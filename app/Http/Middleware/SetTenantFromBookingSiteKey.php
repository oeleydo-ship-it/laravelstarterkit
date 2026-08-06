<?php

namespace App\Http\Middleware;

use App\Models\BookingSite;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromBookingSiteKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $siteKey = (string) $request->route('siteKey');

        $site = BookingSite::withoutGlobalScopes()
            ->where('public_key', $siteKey)
            ->first();

        abort_if(! $site, 404);

        $tenant = Tenant::query()->find($site->tenant_id);

        abort_if(! $tenant || ! $tenant->isModuleEnabled('bookings'), 404);

        app()->instance('tenant', $tenant);
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('booking_site', $site);

        return $next($request);
    }
}
