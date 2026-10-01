<?php

use App\Http\Middleware\BlockRestrictedRoleActions;
use App\Http\Middleware\RequireBusiness;
use App\Http\Middleware\RequireRole;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Every request reaches Laravel through the nginx edge, which is the only
        // thing on the compose network that can reach the backend: no compose file
        // publishes a host port for it, all of them use `expose` only. So the
        // X-Forwarded-* headers can only have come from our own proxy.
        //
        // Without this, a TLS-terminating load balancer in front of nginx makes
        // every generated URL come out as http:// and $request->secure() return
        // false, which breaks signed links, URA fiscalisation callbacks and any
        // absolute URL in an API response.
        $middleware->trustProxies(at: '*');

        $middleware->api(append: [
            BlockRestrictedRoleActions::class,
        ]);

        // Runs after auth:sanctum so the caller is resolved before the tenant check.
        // currentUser() resolves the guard defensively either way, but ordering it last
        // means the common path reads $request->user() directly instead of re-driving
        // the guard.
        $middleware->api(append: [
            RequireBusiness::class,
        ]);

        $middleware->alias([
            'role' => RequireRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (
            AuthenticationException $e,
            $request
        ) {

            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }

        });
    })->create();
