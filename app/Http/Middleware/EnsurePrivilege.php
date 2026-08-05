<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePrivilege
{
    public function handle(Request $request, Closure $next, string ...$privileges)
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        foreach ($privileges as $privilege) {
            if ($user->hasPrivilege($privilege)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'You do not have permission to perform this action.'], 403);
        }

        return redirect()->route('dashboard')
            ->with('error', 'You do not have permission to access that page.');
    }
}
