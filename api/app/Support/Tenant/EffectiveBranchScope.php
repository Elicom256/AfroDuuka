<?php

namespace App\Support\Tenant;

use App\Models\BusinessBranch;
use App\Models\User;
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
     *         via_business_id is null when the resolution is per-branch
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
     * Apply the effective branch constraint to a query builder for the current user.
     */
    public static function apply(\Illuminate\Database\Eloquent\Builder $builder): void
    {
        if (! Auth::check()) {
            return;
        }

        $resolved = static::branchesFor(Auth::user());

        // unrestricted (system role)
        if ($resolved === null) {
            return;
        }

        [$viaBusiness, $branchIds] = $resolved;

        if (blank($branchIds)) {
            // core admin with zero branches -> nothing visible
            $builder->whereRaw('1 = 0');
            return;
        }

        $builder->whereIn('business_branch_id', $branchIds);
    }
}
