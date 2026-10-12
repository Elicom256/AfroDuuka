<?php

namespace App\Policies;

use App\Models\Printer;
use App\Models\User;
use App\Support\Auth\RolePermissions;

class PrinterPolicy
{
    public function viewAny(User $user): bool
    {
        return RolePermissions::canManageBranch($user);
    }

    public function view(User $user, Printer $printer): bool
    {
        return RolePermissions::canManageBranch($user);
    }

    public function create(User $user): bool
    {
        return RolePermissions::canManageBranch($user);
    }

    public function update(User $user, Printer $printer): bool
    {
        return RolePermissions::canManageBranch($user);
    }

    public function delete(User $user, Printer $printer): bool
    {
        return RolePermissions::canManageBranch($user);
    }
}