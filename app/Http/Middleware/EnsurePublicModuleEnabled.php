<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePublicModuleEnabled
{
    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        abort_unless($tenant && $tenant->isModuleEnabled($moduleKey), 404);

        return $next($request);
    }
}
