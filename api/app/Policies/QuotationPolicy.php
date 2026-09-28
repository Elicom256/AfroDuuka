<?php

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;
use App\Support\Tenant\EffectiveBranchScope;

class QuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Quotation $quotation): bool
    {
        return $this->isWithinBranchSet($user, $quotation);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Quotation $quotation): bool
    {
        return $this->isWithinBranchSet($user, $quotation);
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        $role = strtolower((string) $user->role?->name);
        return in_array($role, ['executive', 'coresupport', 'siteadmin'], true);
    }

    private function isWithinBranchSet(User $user, Quotation $quotation): bool
    {
        $resolved = EffectiveBranchScope::branchesFor($user);

        if ($resolved === null) {
            return true;
        }

        return in_array($quotation->business_branch_id, $resolved[1], true);
    }
}