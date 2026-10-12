<?php

namespace App\Policies;

use App\Models\Salary;
use App\Models\User;
use App\Support\Auth\RolePermissions;

class SalaryPolicy
{
    /**
     * Salary figures are sensitive: they disclose one role's pay against another's
     * across the whole business, so even the read is restricted to the roles that
     * run payroll rather than every tenant user.
     */
    public function viewAny(User $user): bool
    {
        return RolePermissions::canManageSensitiveFinance($user);
    }

    public function view(User $user, Salary $salary): bool
    {
        return RolePermissions::canManageSensitiveFinance($user);
    }

    public function create(User $user): bool
    {
        return RolePermissions::canManageSensitiveFinance($user);
    }

    public function update(User $user, Salary $salary): bool
    {
        return RolePermissions::canManageSensitiveFinance($user);
    }

    public function delete(User $user, Salary $salary): bool
    {
        return RolePermissions::canManageSensitiveFinance($user);
    }
}
