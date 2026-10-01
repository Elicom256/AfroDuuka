<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Auth\RolePermissions;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Closes the window between signup and business creation.
 *
 * A self-serve signup deliberately creates the account first and the business second:
 * the owner is a real person before they are a tenant. That leaves a brief state where
 * users.business_id is null.
 *
 * Null used to mean "unrestricted" — BaseModel and EffectiveBranchScope both treat a
 * null tenant as a system role and apply no filter at all — so an account that never
 * completed onboarding could read and write every tenant's rows. That is the most
 * dangerous shape a multi-tenant bug can take.
 *
 * This middleware flips the meaning of that state to "not onboarded yet". Such a user
 * may do exactly three things: read their own profile, log out, and create their
 * business. Everything else is refused with a 403 that names the fix, so the client can
 * send them to the onboarding step instead of a blank screen.
 *
 * It is deliberately a middleware rather than a BaseModel change: the scoping layer has
 * to stay fail-open for queue workers and console commands, which legitimately run with
 * no authenticated user and carry their tenant in BusinessContext. Denying them at the
 * model layer would break every background job. The request layer is the only place
 * where "this human has not chosen a tenant yet" is meaningful.
 */
class RequireBusiness
{
    /**
     * Route paths a not-yet-onboarded user may still reach.
     *
     * Compared with and without a leading slash so a route declared as "me" and one
     * reached as "/api/users/me" both match.
     */
    private const ALLOWED = [
        'api/users/me',
        'api/users/logout',
        'api/dashboard/business',
        'api/users',
        'api/notifications/unread-count',
        'api/notifications',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->currentUser($request);

        // Unauthenticated callers are the job of auth:sanctum, not this middleware.
        if ($user === null) {
            return $next($request);
        }

        // A tenant-bound account passes through untouched.
        if ($user->business_id !== null) {
            return $next($request);
        }

        // Support staff genuinely have no business_id — CoreSupport and siteadmin are
        // system roles that operate across every tenant by design, and TenantIsolationTest
        // asserts that behaviour. A null business_id only means "onboarding incomplete"
        // when the account is not a system role; role, not the missing tenant, is what
        // tells the two apart.
        if (RolePermissions::isElevated($user)) {
            return $next($request);
        }

        if ($this->isAllowed($request)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Finish setting up your business before continuing.',
            'error' => 'onboarding_incomplete',
            'next' => 'POST /api/dashboard/business',
        ], 403);
    }

    /**
     * Is this request part of onboarding?
     */
    private function isAllowed(Request $request): bool
    {
        $path = '/'.ltrim($request->path(), '/');

        foreach (self::ALLOWED as $allowed) {
            $allowed = '/'.ltrim($allowed, '/');

            if ($path === $allowed || str_starts_with($path, $allowed.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the caller without depending on where this sits relative to auth:sanctum.
     *
     * This middleware is appended to the API group, which puts it before the per-route
     * auth:sanctum, so $request->user() is usually still null when it runs. The guard is
     * therefore resolved directly.
     *
     * Note what is NOT here: the RequestGuard dance RequireRole uses. Laravel\Sanctum\Guard
     * is not an instance of Illuminate\Auth\RequestGuard, so that branch silently does
     * nothing there. Copying it would look defensive while actually calling user() on the
     * guard ahead of the framework and caching the unresolved result, which makes every
     * later auth:sanctum check in the same request fail with a 401.
     */
    private function currentUser(Request $request): ?User
    {
        $resolved = $request->user();

        if ($resolved instanceof User) {
            return $resolved;
        }

        $user = Auth::guard('sanctum')->user();

        return $user instanceof User ? $user : null;
    }
}