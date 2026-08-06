<?php
namespace App\Http\Middleware;
use App\Models\FormSite; use App\Models\Tenant; use Closure; use Illuminate\Http\Request; use Symfony\Component\HttpFoundation\Response;
class SetTenantFromFormSiteKey {
 public function handle(Request $request, Closure $next): Response { $site=FormSite::withoutGlobalScopes()->where('public_key',(string)$request->route('siteKey'))->first(); abort_if(!$site,404); $tenant=Tenant::query()->find($site->tenant_id); abort_if(!$tenant||!$tenant->isModuleEnabled('forms'),404); app()->instance('tenant',$tenant); $request->attributes->set('tenant',$tenant); $request->attributes->set('form_site',$site); return $next($request); }
}
