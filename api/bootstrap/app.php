<?php

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
        $middleware->api(append: [
            \App\Http\Middleware\BlockRestrictedRoleActions::class,
        ]);

        // Runs after auth:sanctum so the caller is resolved before the tenant check.
        // currentUser() resolves the guard defensively either way, but ordering it last
        // means the common path reads $request->user() directly instead of re-driving
        // the guard.
        $middleware->api(append: [
            \App\Http\Middleware\RequireBusiness::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\RequireRole::class,
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
