<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;
use App\Support\Auth\RolePermissions;

class SubscriptionPolicy
{
    /**
     * Read is same-tenant, not elevated.
     *
     * Billing pages exist for the business paying the bill — PlanBillingSettings and
     * ExecutiveSubscriptionPaymentsPage both read through this — so returning false
     * here, as this policy used to, was wrong in the other direction: the list was
     * only reachable because SubscriptionController::index never called the policy.
     * The tenant scope on Subscription is what actually keeps one business from
     * reading another's billing, so the policy only has to agree with it.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $user->business_id === null
            || $user->business_id === $subscription->business_id;
    }

    /**
     * Writes go through canManageSubscriptions, which is Elevated roles only. The
     * tenant check still applies: a CoreSupport or siteadmin account with no
     * business_id may act across tenants by design, which is what RequireBusiness
     * and the cross-tenant tests already encode.
     */
    public function create(User $user): bool
    {
        return RolePermissions::canManageSubscriptions($user);
    }

    public function update(User $user, Subscription $subscription): bool
    {
        if (! RolePermissions::canManageSubscriptions($user)) {
            return false;
        }

        return $user->business_id === null
            || $user->business_id === $subscription->business_id;
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        if (! RolePermissions::canManageSubscriptions($user)) {
            return false;
        }

        return $user->business_id === null
            || $user->business_id === $subscription->business_id;
    }

    public function restore(User $user, Subscription $subscription): bool
    {
        return false;
    }

    public function forceDelete(User $user, Subscription $subscription): bool
    {
        return false;
    }
}
