<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Support\Tenant\EffectiveBranchScope;

class ProductPolicy
{
    /**
     * Determine whether the user can view any products.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Product $product): bool
    {
        return $this->isWithinBranchSet($user, $product);
    }

    /**
     * Determine whether the user can create products.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Product $product): bool
    {
        return $this->isWithinBranchSet($user, $product);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): bool
    {
        return $this->isWithinBranchSet($user, $product);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Product $product): bool
    {
        return $this->isWithinBranchSet($user, $product);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return $this->isWithinBranchSet($user, $product);
    }

    private function isWithinBranchSet(User $user, Product $product): bool
    {
        $resolved = EffectiveBranchScope::branchesFor($user);

        if ($resolved === null) {
            return true;
        }

        return in_array($product->business_branch_id, $resolved[1], true);
    }
}
