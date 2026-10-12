<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Support\Auth\RolePermissions;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Customer $customer): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return RolePermissions::canManageBranch($user);
    }

    public function update(User $user, Customer $customer): bool
    {
        return RolePermissions::canManageBranch($user);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return RolePermissions::canManageBranch($user);
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $this->delete($user, $customer);
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return $this->delete($user, $customer);
    }
}
