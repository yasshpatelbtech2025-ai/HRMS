<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Roles: admin-only pages, no deletes for users, deactivated accounts signed out.
        $middleware->appendToGroup('web', \App\Http\Middleware\EnforceRoleAccess::class);
        // The top-bar company carries across pages (?company=).
        $middleware->appendToGroup('web', \App\Http\Middleware\UseCurrentCompany::class);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Behind a forwarding proxy (e.g. a GitHub Codespaces link) the app must trust its
        // X-Forwarded-* headers, or it links styles and scripts to an address the browser
        // can't reach. Off unless TRUSTED_PROXIES is set ("*" or a comma-separated list).
        if ($proxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
