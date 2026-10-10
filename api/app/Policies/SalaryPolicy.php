<?php

namespace App\Policies;

use App\Models\Salary;
use App\Models\User;
use App\Support\Auth\RolePermissions;

class SalaryPolicy
{
    /**
     * A salary is payroll configuration, so reading it is gated the same way
     * writing it is. The previous EmployeeSalaryPolicy returned true for every
     * ability, which meant any authenticated user could read the whole payroll.
     */
    public function viewAny(User $user): bool
    {
        return $this->manage($user);
    }

    public function view(User $user, Salary $salary): bool
    {
        return $this->manage($user);
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Salary $salary): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, Salary $salary): bool
    {
        return $this->manage($user);
    }

    private function manage(User $user): bool
    {
        return RolePermissions::hasAnyRole($user, ['executive', 'branch_manager', 'coresupport', 'siteadmin']);
    }
}
