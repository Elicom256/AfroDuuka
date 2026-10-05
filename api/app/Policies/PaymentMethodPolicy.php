<?php

namespace App\Policies;

use App\Models\CoreSettings\PaymentMethod;
use App\Models\User;
use App\Support\Auth\RolePermissions;

class PaymentMethodPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, PaymentMethod $paymentMethod): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, PaymentMethod $paymentMethod): bool
    {
        return false;
    }

    public function delete(User $user, PaymentMethod $paymentMethod): bool
    {
        return RolePermissions::hasAnyRole($user, ['executive', 'branch_manager', 'coresupport', 'siteadmin']);
    }

    public function restore(User $user, PaymentMethod $paymentMethod): bool
    {
        return false;
    }

    public function forceDelete(User $user, PaymentMethod $paymentMethod): bool
    {
        return RolePermissions::hasAnyRole($user, ['executive', 'branch_manager', 'coresupport', 'siteadmin']);
    }
}
