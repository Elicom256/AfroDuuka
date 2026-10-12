<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use App\Support\Auth\RolePermissions;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Expense $expense): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return RolePermissions::canManageBranch($user);
    }

    public function update(User $user, Expense $expense): bool
    {
        return RolePermissions::canManageBranch($user);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return RolePermissions::canManageBranch($user);
    }

    public function approve(User $user, Expense $expense): bool
    {
        return RolePermissions::canManageSensitiveFinance($user);
    }
}
