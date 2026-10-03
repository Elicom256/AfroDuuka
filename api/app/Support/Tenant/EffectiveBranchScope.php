<?php

namespace App\Support\Tenant;

use App\Models\BusinessBranch;
use App\Models\User;
use App\Support\Auth\RolePermissions;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EffectiveBranchScope
{
    /**
     * Resolve the business_branch_ids the given user may see.
     *
     * Resolution order (per scope_branch.md §3.1):
     *   1. user has no business_id        -> null      (system role / CoreSupport: unrestricted)
     *   2. business_id, no business_branch_id -> all branches of the business (Executive)
     *   3. otherwise                      -> [business_branch_id]
     *
     * @return array{0:int|null,1:int[]} [via_business_id, branch_ids]
     *                                   via_business_id is null when the resolution is per-branch
     */
    public static function branchesFor(?User $user): ?array
    {
        if (! $user || ! $user->business_id) {
            return null;
        }

        if (! $user->business_branch_id) {
            $ids = BusinessBranch::where('business_id', $user->business_id)
                ->pluck('id')
                ->all();

            return [1, $ids];
        }

        return [null, [$user->business_branch_id]];
    }

    /**
     * Resolve the branch a report should be scoped to, from user input.
     *
     * Reports are per branch: one document describes one branch's month, never a
     * blended "company total" that reads as if it were somebody's branch.
     *
     * This deliberately reuses branchesFor() so the permitted set can never drift from
     * the rows the scope actually admits. Asking the scope first is not enough on its
     * own: a business executive's scope admits every branch of their business, so a
     * branch_id typed into a query string would otherwise be treated as a permission
     * when it is only a preference.
     *
     * @param  mixed  $requested  the branch_id from the request, if any
     * @return int the branch id to scope to
     *
     * @throws ValidationException when the branch is not permitted
     * @throws HttpException when a named branch is not the caller's
     */
    public static function resolveReportBranch(?User $user, mixed $requested): int
    {
        $resolved = static::branchesFor($user);

        if ($resolved === null) {
            // A system role with no business is never in this position: both callers
            // require an authenticated business first. Failing closed rather than
            // open means a future caller that forgets that check cannot accidentally
            // report on whichever branch a request happened to name.
            throw ValidationException::withMessages([
                'branch_id' => 'No business is associated with this account.',
            ]);
        }

        $allowed = array_map('intval', $resolved[1]);

        if (filled($requested)) {
            $branchId = (int) $requested;

            abort_unless(
                $branchId > 0 && in_array($branchId, $allowed, true),
                403,
                'You do not have access to that branch.',
            );

            return $branchId;
        }

        if (count($allowed) === 1) {
            return $allowed[0];
        }

        throw ValidationException::withMessages([
            'branch_id' => count($allowed) === 0
                ? 'This business has no branches to report on.'
                : 'Select a branch. Each branch has its own report.',
        ]);
    }

    /**
     * Apply the effective branch constraint to a query builder.
     *
     * Falls back to BusinessContext when there is no authenticated user. Without this,
     * every queued and scheduled job would read branch rows unfiltered — and because
     * most branch-scoped tables (products included) carry no business_id column, that
     * meant reading across every tenant, not merely every branch.
     */
    public static function apply(EloquentBuilder $builder): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            $resolved = static::branchesFor($user);

            // A user with no business is one of two things: a system role that
            // operates across every tenant by design (siteadmin, CoreSupport — the
            // latter is elevated in RolePermissions for exactly that reason), or an
            // account that has not finished creating its business. Only the first may
            // see everything; the second must see nothing. RequireBusiness already
            // refuses the second at the route layer, but the model layer has to fail
            // closed on its own for jobs and console commands.
            //
            // This used to test isSiteAdmin() alone, so a CoreSupport account was
            // given `0 = 1` and could read nothing at all — the opposite of the
            // cross-tenant access its role exists to provide.
            if ($resolved === null) {
                if (! RolePermissions::isPlatformOperator($user)) {
                    $builder->whereRaw('0 = 1');
                }

                return;
            }

            [$viaBusiness, $branchIds] = $resolved;
        } else {
            $context = app(BusinessContext::class);

            // No authenticated user and no tenant context: there is nothing to scope by.
            // Callers that need scoping in a job must run inside BusinessContext::run().
            if (! $context->hasBusiness()) {
                return;
            }

            if ($context->branchId() !== null) {
                $branchIds = [$context->branchId()];
            } else {
                $branchIds = BusinessBranch::where('business_id', $context->businessId())
                    ->pluck('id')
                    ->all();
            }
        }

        // A NULL business_branch_id marks a business-level row: a business-wide
        // notification, an owner-level recipient, a delivery not tied to a branch.
        // It belongs to the business and not to any competing branch, so it stays
        // visible to every branch of that business.
        //
        // This is not cosmetic. `whereIn('business_branch_id', [...])` never matches
        // NULL, so without the orWhereNull a business that stores its notifications
        // business-wide sees none of them — the tenant's own delivery history looks
        // empty while a neighbouring tenant's does not. The same applies to a
        // business with zero branches: it still owns its business-level rows, so a
        // blanket `1 = 0` is wrong there too and the restriction has to be applied
        // to branch rows only.
        //
        // Grouped so it still ANDs with the business scope rather than widening it.
        $builder->where(function ($query) use ($branchIds) {
            $query->whereNull('business_branch_id');

            if (filled($branchIds)) {
                $query->orWhereIn('business_branch_id', $branchIds);
            }
        });
    }
}
