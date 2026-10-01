<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SiteAdminPolicy
{
    /**
     * Determine whether the user can manage any business.
     */
    public function manageAny(User $user): bool
    {
        return $user->role?->name === 'siteadmin';
    }

    /**
     * Determine whether the user can manage the business.
     */
    public function manage(User $user, Business $business): bool
    {
        return $user->role?->name === 'siteadmin';
    }
}