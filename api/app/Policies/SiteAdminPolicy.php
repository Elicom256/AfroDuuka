<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;
use App\Support\Auth\RolePermissions;

class SiteAdminPolicy
{
    /**
     * Determine whether the user can manage any business.
     */
    public function manageAny(User $user): bool
    {
        return RolePermissions::hasAnyRole($user, ['siteadmin']);
    }

    /**
     * Determine whether the user can manage the business.
     */
    public function manage(User $user, Business $business): bool
    {
        return RolePermissions::hasAnyRole($user, ['siteadmin']);
    }
}
