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
 * Denies destructive verbs to roles that have no delete authority.
 *
 * This sits in front of every api route rather than in each policy because "Operations
 * may not delete anything" is a statement about the role, not about a model. Enforcing
 * it per-controller meant each of the ~70 controllers had to remember, and 49 of them
 * have a destroy() with no authorize() call at all. Branch scoping already guarantees
 * a restricted user can only ever reach their own branch's rows; this guarantees they
 * can never remove them.
 *
 * The SES webhook is a POST and is deliberately outside the api group (routes/api.php),
 * so an SNS signature is still accepted.
 */
class BlockRestrictedRoleActions
{
    /**
     * Verbs that remove data. PUT/PATCH/POST are not included: a restricted role needs
     * POST for sales and receipts, and PUT for the stock counts it does own.
     */
    private const DESTRUCTIVE_VERBS = ['DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), self::DESTRUCTIVE_VERBS, true)) {
            return $next($request);
        }

        if (RolePermissions::isRestricted($this->currentUser($request))) {
            return response()->json([
                'message' => 'Your role is not permitted to delete records.',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Resolve the caller without depending on where this sits relative to auth:sanctum.
     *
     * `auth:sanctum` is applied per route file while this is api group middleware, and
     * nothing in the framework pins the two against each other. A check that read a
     * null user would treat every restricted caller as anonymous and wave the delete
     * straight through — the exact failure this class exists to prevent. So the role is
     * resolved from *this* request rather than assumed to be there already.
     *
     * The guard is asked against the request handed to us, not the container's. In the
     * pipeline they are the same object, so this is a no-op there; it matters when the
     * middleware is exercised on its own, where reading the container's request would
     * silently return no user and turn the check into a rubber stamp.
     *
     * With no valid token this returns null, the delete passes through, and auth:sanctum
     * answers 401 as usual.
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
