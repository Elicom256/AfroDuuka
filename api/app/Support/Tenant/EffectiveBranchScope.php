<?php

namespace App\Support\Tenant;

use App\Models\BusinessBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\Auth;

class EffectiveBranchScope
{
    /**
     * Resolve the business_branch_ids the given user may see.
     *
     * Resolution order (per scope_branch.md §3.1):
     *   1. user has no business_id        -> null      (system role / superadmin: unrestricted)
     *   2. business_id, no business_branch_id -> all branches of the business (business admin)
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
            $resolved = static::branchesFor(Auth::user());

            // unrestricted (system role)
            if ($resolved === null) {
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
