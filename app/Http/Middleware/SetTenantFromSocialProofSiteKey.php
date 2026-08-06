<?php

namespace App\Http\Middleware;

use App\Models\SocialProofSite;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromSocialProofSiteKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $site = SocialProofSite::withoutGlobalScopes()
            ->where('public_key', (string) $request->route('siteKey'))
            ->first();

        abort_if(! $site, 404);

        $tenant = Tenant::find($site->tenant_id);
        abort_if(! $tenant || ! $tenant->isModuleEnabled('socialproof'), 404);

        app()->instance('tenant', $tenant);
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('social_proof_site', $site);

        return $next($request);
    }
}
