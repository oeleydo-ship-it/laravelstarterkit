<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The chat widget is loaded in a cross-origin iframe on customer sites.
 * Hosting panels and some stacks default to X-Frame-Options: SAMEORIGIN,
 * which leaves an empty iframe box with no launcher. Strip that header and
 * allow framing via CSP instead.
 */
class AllowChatWidgetFraming
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->remove('X-Frame-Options');

        // Prefer CSP; do not clobber a richer policy if one already exists.
        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', 'frame-ancestors *');
        }

        return $response;
    }
}
