<?php

namespace App\Policies;

use App\Models\Plan;
use App\Models\User;
use App\Support\Auth\RolePermissions;

class PlanPolicy
{
    public function viewAny(User $user): bool
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
}
