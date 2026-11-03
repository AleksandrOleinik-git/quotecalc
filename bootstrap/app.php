<?php

use App\Http\Middleware\EnsureApiKey;
use App\Pricing\PricingException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['api.key' => EnsureApiKey::class]);
        // Behind a TLS-terminating reverse proxy (Traefik, Nginx, load balancer) signed URLs must see https.
        $middleware->trustProxies(at: '*');
        // Live pricing is read-only and throttled; skipping CSRF keeps it working when the session rotates (e.g. after an admin login in another tab).
        $middleware->validateCsrfTokens(except: ['calculator/price']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A configuration the catalogue can't price is a user input problem: answer with 422 / back with errors.
        $exceptions->map(
            PricingException::class,
            fn (PricingException $e) => ValidationException::withMessages([$e->field => $e->getMessage()]),
        );
        $exceptions->shouldRenderJsonWhen(
            fn ($request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
