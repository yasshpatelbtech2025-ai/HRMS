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

        // Published through a Cloudflare Tunnel: cloudflared on this server forwards to
        // localhost, so trust X-Forwarded-* (notably Proto=https) only from loopback.
        // Fixed here because .env isn't loaded yet when this runs (nor with config:cache).
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
