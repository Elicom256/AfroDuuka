<?php

namespace App\Policies;

use App\Models\Plan;
use App\Models\User;
use App\Support\Auth\RolePermissions;

/**
 * Plans are platform-wide pricing, not tenant data.
 *
 * `App\Models\Plan` deliberately extends Eloquent\Model rather than BaseModel:
 * a plan has no business_id, because one price list is sold to every tenant.
 * That also means no global scope confines the reads and writes, so this policy
 * is the only thing standing between any signed-in user of any tenant and the
 * ability to reprice or delete the entire catalogue.
 *
 * Reading is public — the signup screen shows plans before a user has one.
 * Writing is restricted to system roles, following SuperAdminBusinessController,
 * which already treats siteadmin as the platform operator.
 */
class PlanPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(User $user, Plan $plan): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return RolePermissions::isPlatformOperator($user);
    }

    public function update(User $user, Plan $plan): bool
    {
        return RolePermissions::isPlatformOperator($user);
    }

    public function delete(User $user, Plan $plan): bool
    {
        return RolePermissions::isPlatformOperator($user);
    }

    public function restore(User $user, Plan $plan): bool
    {
        return RolePermissions::isPlatformOperator($user);
    }

    public function forceDelete(User $user, Plan $plan): bool
    {
        return RolePermissions::isPlatformOperator($user);
    }
}
