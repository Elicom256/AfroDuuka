<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CoreSettings\PaymentMethod;
use Illuminate\Auth\Access\Response;

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
        return false;
    }

    public function restore(User $user, PaymentMethod $paymentMethod): bool
    {
        return false;
    }

    public function forceDelete(User $user, PaymentMethod $paymentMethod): bool
    {
        return false;
    }
}
