<?php

namespace App\Http\Middleware;

use App\Models\ReviewSite;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromReviewSiteKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $site = ReviewSite::withoutGlobalScopes()->where('public_key', (string) $request->route('siteKey'))->first();
        abort_if(! $site, 404);
        $tenant = Tenant::find($site->tenant_id);
        abort_if(! $tenant || ! $tenant->isModuleEnabled('reviews'), 404);
        app()->instance('tenant', $tenant);
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('review_site', $site);
        return $next($request);
    }
}
