<?php

namespace App\Policies;

use App\Models\ProductCategory;
use App\Models\User;
use App\Support\Auth\RolePermissions;

class ProductCategoryPolicy
{
    /**
     * Determine whether the user can view any categories.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProductCategory $productCategory): bool
    {
        return $this->isWithinBusiness($user, $productCategory);
    }

    /**
     * Determine whether the user can create categories.
     *
     * A category is catalogue furniture: it decides what the executive's products are
     * filed under, so it sits behind the same gate as creating the products.
     */
    public function create(User $user): bool
    {
        return RolePermissions::canCreateCatalog($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProductCategory $productCategory): bool
    {
        return RolePermissions::canEditCatalog($user)
            && $this->isWithinBusiness($user, $productCategory);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProductCategory $productCategory): bool
    {
        return RolePermissions::canDelete($user)
            && $this->isWithinBusiness($user, $productCategory);
    }

    private function isWithinBusiness(User $user, ProductCategory $productCategory): bool
    {
        // A system role with no business_id is unrestricted, matching the branch scope.
        if (! $user->business_id) {
            return true;
        }

        return (int) $productCategory->business_id === (int) $user->business_id;
    }
}
