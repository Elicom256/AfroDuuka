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
 * This sits in front of every api route rather than in each policy because "may not
 * delete" is a statement about the role, not about a model. Enforcing it per-controller
 * meant each of the ~70 controllers had to remember, and most have a destroy() with no
 * authorize() call at all. Branch scoping already guarantees a confined user can only
 * ever reach their own branch's rows; this guarantees they can never remove them.
 *
 * The rule is an allowlist (RolePermissions::canDelete), not a denylist. It was
 * originally `isRestricted()` — "deny Operations" — which is only correct while
 * Operations is the sole role without delete rights. It is not: Procurement holds no
 * delete authority either, but because it was absent from RESTRICTED_ROLES it sailed
 * through every DELETE in the app, including tenant-wide finance rows and stock
 * transfers. An allowlist fails closed for any role added later, which is the safer
 * default for a verb that cascades.
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

        $user = $this->currentUser($request);

        // No resolvable user is not a role problem, it is an authentication problem.
        // Refusing here would answer 403 where auth:sanctum owes a 401, and would leak
        // that this route exists behind a token. auth:sanctum refuses the request.
        if ($user === null) {
            return $next($request);
        }

        if (! RolePermissions::canDelete($user)) {
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
