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
        // The mini PC deployment sits behind Cloudflare Tunnel (cloudflared) → nginx,
        // both internal to the Docker network — nginx itself is never reachable
        // directly from the internet, so trusting every proxy hop here is safe. Without
        // this, Laravel can't tell the original request was HTTPS (breaks secure
        // cookies and https:// URL generation) even though the browser sees HTTPS the
        // whole way. Harmless locally too — dev has no proxy in front of nginx at all.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
