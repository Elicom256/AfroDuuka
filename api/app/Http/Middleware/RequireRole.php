<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Auth\RolePermissions;
use Closure;
use Illuminate\Auth\RequestGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates a route group to executive-level roles.
 *
 * Tenant scoping (BaseModel) already confines every query to the caller's
 * business and branch; this middleware answers the orthogonal question of
 * whether the caller's role may see a resource at all. It leans on the
 * RolePermissions capability map so the rule lives in exactly one place.
 *
 * Elevated roles (executive, coresupport, siteadmin) pass everywhere.
 * BranchManager passes too — EffectiveBranchScope confines it to its own
 * branch, which is the same trade the catalogue and stock rules already make.
 */
class RequireRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->currentUser($request);
        if ($user && RolePermissions::canManageBranch($user)) {
            return $next($request);
        }

        // A user who has not created their business yet has no role, because roles are
        // provisioned with the business. Without this they would be refused here before
        // RequireBusiness could route them to onboarding, and self-serve signup would
        // dead end on a 403. Letting them through is safe: RequireBusiness refuses every
        // tenant route until onboarding completes.
        if ($user && $user->business_id === null && $user->role_id === null) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Your role is not permitted to access this resource.',
        ], 403);
    }

    /**
     * Resolve the caller without depending on where this sits relative to auth:sanctum.
     *
     * `auth:sanctum` is applied per route file while this is route-group middleware,
     * and nothing in the framework pins the two against each other. A check that read a
     * null user would treat every caller as anonymous and refuse the request — the
     * exact failure class BlockRestrictedRoleActions guards against.
     */
    private function currentUser(Request $request): ?User
    {
        $resolved = $request->user();

        if ($resolved instanceof User) {
            return $resolved;
        }

        $guard = Auth::guard('sanctum');

        if ($guard instanceof RequestGuard) {
            $guard->setRequest($request);
        }

        $user = $guard->user();

        return $user instanceof User ? $user : null;
    }
}
