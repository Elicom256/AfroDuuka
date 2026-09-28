<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Support\Auth\RolePermissions;
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
     *
     * Operations may not author catalogue records. Stock they are counting already
     * exists on a product somebody else created.
     */
    public function create(User $user): bool
    {
        return RolePermissions::canCreateCatalog($user);
    }

    /**
     * Determine whether the user can update the model.
     *
     * Allowed within the branch set. What a restricted role may actually change is
     * narrowed in UpdateProductRequest, which permits quantity and nothing else, and
     * the controller routes that quantity through InventoryService so the change is
     * journalled.
     */
    public function update(User $user, Product $product): bool
    {
        return $this->isWithinBranchSet($user, $product);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * Products cascade into stock_movements, sale_items, purchase_items and
     * price_histories, so a delete erases the trail behind every sale of that product.
     * A sale is the fact; the product row is the label on it.
     */
    public function delete(User $user, Product $product): bool
    {
        return RolePermissions::canDelete($user)
            && $this->isWithinBranchSet($user, $product);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Product $product): bool
    {
        return $this->delete($user, $product);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return $this->delete($user, $product);
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
