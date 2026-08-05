<?php

namespace App\Http\Middleware;

use App\Services\Chat\ApiTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bearer-token auth for the chat REST API. There is no logged-in user here, so
 * the token is what establishes the tenant — everything downstream then runs
 * under the same global scope the web UI uses.
 */
class AuthenticateChatApi
{
    public function __construct(protected ApiTokenService $tokens)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->tokens->resolve($request->bearerToken());

        if (! $token) {
            return response()->json(['message' => 'Invalid or missing API token.'], 401);
        }

        $tenant = $token->tenant;

        if (! $tenant || ! $tenant->isModuleEnabled('chat')) {
            return response()->json(['message' => 'Live chat is not enabled for this workspace.'], 403);
        }

        app()->instance('tenant', $tenant);
        $request->attributes->set('chat_api_token', $token);

        // Coarse timestamp only: writing on every call would be a row update per
        // request, and "last used today" is all this is for.
        if (! $token->last_used_at || $token->last_used_at->lt(now()->subMinutes(5))) {
            $token->forceFill(['last_used_at' => now()])->save();
        }

        return $next($request);
    }
}
