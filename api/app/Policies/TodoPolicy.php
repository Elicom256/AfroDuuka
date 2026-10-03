<?php

namespace App\Policies;

use App\Models\Todo;
use App\Models\User;
use App\Support\Auth\RolePermissions;

/**
 * A todo belongs to the user who created it.
 *
 * The tenant check alone is not enough: every user in a business shares a
 * business_id, so "same business" would let anyone rewrite or close anyone
 * else's task. Ownership is the rule; the business check is what stops a stale
 * row from another tenant being touched through a leaked id.
 */
class TodoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Todo $todo): bool
    {
        return $user->business_id === $todo->business_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * The owner may edit their own todo. Managers may edit any todo in the
     * business, which is what makes reassignment possible without handing
     * every user write access to everyone else's list.
     */
    public function update(User $user, Todo $todo): bool
    {
        if ($user->business_id !== $todo->business_id) {
            return false;
        }

        return $todo->user_id === $user->id || RolePermissions::canManageBranch($user);
    }

    /**
     * Closing your own task is the owner's call. Removing the row entirely is
     * a records decision, so it stays with roles that may delete.
     */
    public function delete(User $user, Todo $todo): bool
    {
        if ($user->business_id !== $todo->business_id) {
            return false;
        }

        return RolePermissions::canDelete($user);
    }
}