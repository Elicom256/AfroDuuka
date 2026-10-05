<?php

namespace App\Policies;

use App\Models\EmployeeSalary;
use App\Models\User;
use App\Support\Auth\RolePermissions;

class EmployeeSalaryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, EmployeeSalary $employeeSalary): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, EmployeeSalary $employeeSalary): bool
    {
        return true;
    }

    public function delete(User $user, EmployeeSalary $employeeSalary): bool
    {
        return RolePermissions::hasAnyRole($user, ['executive', 'branch_manager', 'coresupport', 'siteadmin']);
    }
}
