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
        // Artisan/package discovery may boot the HTTP stack before migrations.
        // In that case Laravel's normal error handling should remain in charge.
        if (! Schema::hasTable('users')) {
            return $next($request);
        }

        // Only an entirely fresh installation may enter web setup. This avoids
        // exposing account creation on an older database whose administrator
        // may have been removed intentionally or accidentally.
        $requiresSetup = ! User::withoutGlobalScopes()->exists();

        if ($requiresSetup && ! $request->routeIs('setup.*')) {
            return redirect()->route('setup.create');
        }

        return $next($request);
    }
}
