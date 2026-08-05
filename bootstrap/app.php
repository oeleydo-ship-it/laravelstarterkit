<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The chat widget is embedded on external visitor-facing sites and has
        // no Laravel session/CSRF token to send; it authenticates requests via
        // the visitor token instead.
        $middleware->validateCsrfTokens(except: ['widget/*']);

        $middleware->alias([
            'privilege' => \App\Http\Middleware\EnsurePrivilege::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
