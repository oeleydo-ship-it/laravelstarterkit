<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class RequireSuperAdminSetup
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('setup.required')) {
            return $next($request);
        }

        // Artisan/package discovery may boot the HTTP stack before migrations.
        // In that case Laravel's normal error handling should remain in charge.
        if (! Schema::hasTable('users')) {
            return $next($request);
        }

        $requiresSetup = ! User::withoutGlobalScopes()
            ->where('is_superadmin', true)
            ->exists();

        if ($requiresSetup && ! $request->routeIs('setup.*')) {
            return redirect()->route('setup.create');
        }

        return $next($request);
    }
}
