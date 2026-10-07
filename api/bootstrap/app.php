<?php

use App\Http\Middleware\BlockRestrictedRoleActions;
use App\Http\Middleware\RecoverFromAbortedTransaction;
use App\Http\Middleware\RequireBusiness;
use App\Http\Middleware\RequireRole;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    /**
     * Listener discovery off.
     *
     * withEvents() registers Laravel's own EventServiceProvider, whose
     * shouldDiscoverEvents() is true only for that exact class — so it scans
     * app/Listeners and registers every handle(Event $e) by parameter type, as
     * `Listener@handle`. Every listener in here is also mapped by hand in
     * App\Providers\WhatsAppEventServiceProvider and App\Providers\EventServiceProvider,
     * so all of them ran twice.
     *
     * The duplicate was not cosmetic. BusinessRegistered fired its listener twice during
     * signup, and each run resolves a WhatsAppConfig with a find-or-create: the second
     * one's SELECT is blinded by the tenant scope — the owner has no business_id yet, so
     * BaseModel resolves it to `whereRaw('0 = 1')` — so it cannot see the row the first
     * one just inserted, and it inserts a duplicate against the unique index on
     * business_id. On a synchronous queue that is a 500 on the signup itself; on the
     * database queue it is a failed job nobody reads.
     *
     * The explicit provider maps are the source of truth here, so discovery adds nothing.
     */
    ->withEvents(discover: false)
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

        // First in the api group so it wraps every later middleware and the
        // controller: an inherited aborted transaction (SQLSTATE 25P02) rolls
        // back, reconnects and replays the request exactly once.
        $middleware->api(prepend: [
            RecoverFromAbortedTransaction::class,
        ]);

        // Never redirect an unauthenticated caller to a login page.
        //
        // Authenticate's default is `route('login')`, and this application has no such
        // route — it is a JSON API behind an SPA that renders its own login screen. So
        // every protected endpoint reached without a token threw
        // `RouteNotFoundException: Route [login] not defined`, which the exception
        // render below never sees because it is not an AuthenticationException. The
        // result was a 500 on every unauthenticated API call.
        //
        // That is worse than it sounds: the SPA's whole dead-session handling is built
        // on recognising a 401 (store/app/authListener.ts), so an expired token produced
        // a 500, the listener stayed quiet, and the app kept issuing requests with a
        // token that could never work.
        //
        // Returning null makes Authenticate throw AuthenticationException, which the
        // renderer turns into the 401 JSON the client expects.
        $middleware->redirectGuestsTo(fn () => null);

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
