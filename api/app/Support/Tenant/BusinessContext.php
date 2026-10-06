<?php

namespace App\Support\Tenant;

use App\Models\Role;
use App\Models\User;
use App\Support\Auth\RolePermissions;
use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Carries the tenant for work that runs without an authenticated user.
 *
 * BaseModel's global scopes are gated on Auth::check(), so every queued and scheduled
 * job queries across all tenants. For a multi-tenant SaaS that is the most dangerous
 * class of bug there is: a job meant for one business will happily read and write
 * another business's rows.
 *
 * A job wraps its work in run():
 *
 *     app(BusinessContext::class)->run($businessId, fn () => ...);
 *
 * Note the container form. run() is an instance method with no __callStatic, so the
 * static spelling that used to appear in this docblock would have thrown.
 *
 * Every query inside then scopes itself, and nested jobs inherit the context. Context
 * is also cleared on queue job boundaries so a worker process cannot leak one tenant's
 * context into the next job it picks up.
 */
class BusinessContext
{
    protected ?int $businessId = null;

    protected ?int $branchId = null;

    /**
     * The caller's role name, memoised against the user and role it was read for.
     *
     * Keyed on the user id rather than just cached, so switching the acting user
     * inside one process — which tests do — cannot serve a stale role.
     */
    protected ?int $roleNameUserId = null;

    protected ?int $roleNameRoleId = null;

    protected string $roleName = '';

    /**
     * Run a callback with the given tenant active.
     *
     * Nesting is supported: an inner run() restores the outer context on exit rather
     * than clearing it.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function run(int $businessId, Closure $callback, ?int $branchId = null): mixed
    {
        $previousBusinessId = $this->businessId;
        $previousBranchId = $this->branchId;

        $this->businessId = $businessId;
        $this->branchId = $branchId;

        try {
            return $callback();
        } finally {
            $this->businessId = $previousBusinessId;
            $this->branchId = $previousBranchId;
        }
    }

    /**
     * The tenant for the current context, falling back to the authenticated user's
     * business. Null means unrestricted (a genuine system role).
     */
    public function businessId(): ?int
    {
        if ($this->businessId !== null) {
            return $this->businessId;
        }

        $user = Auth::user();
        if (! $user) {
            return null;
        }

        // Platform operators have unrestricted access.
        if ($this->isPlatformOperator()) {
            return null;
        }

        // All other authenticated users have no business context
        // (omitting the clause would grant cross-tenant access)
        return $user->business_id !== null ? (int) $user->business_id : null;
    }

    /**
     * The caller's role name, lowercased for comparison.
     *
     * The Role query is deliberately unscoped. `roles` carries a business_id, so
     * a *scoped* Role query re-enters this method through BaseModel's `business`
     * global scope, which asks for the business id to build the very scope that
     * is asking — and the two call each other until the process runs out of
     * memory. It happened on every authenticated query against any tenant table,
     * which is also why the test suite died with "Premature end of PHP process"
     * the moment it created a record as an Executive.
     *
     * A global scope must never issue a query against a model carrying that same
     * scope; this is the one place that needed the role, and it reads it raw.
     *
     * Memoised because this sits on the hot path: it used to add a SELECT to
     * every single tenant query, and the value cannot change under a given user
     * within a request.
     *
     * "Cannot change within a request" is the part that was wrong. Onboarding
     * creates the account, then the business, then the role — all inside one
     * request — so the first lookup for that user reads role_id null, memoises an
     * empty role name, and every later check in the same process sees the empty
     * string. RequireRole then refuses the very next call as "your role is not
     * permitted", which is how a freshly onboarded owner was locked out of their own
     * business. Keying the memo on role_id as well as user id makes the value
     * invalidate itself the moment the user's role actually changes, which costs
     * nothing on the hot path because role_id is already in memory.
     */
    protected function roleNameFor(?User $user): string
    {
        if (! $user) {
            return '';
        }

        if ($this->roleNameUserId === $user->id && $this->roleNameRoleId === $user->role_id) {
            return $this->roleName;
        }

        $this->roleNameUserId = $user->id;
        $this->roleNameRoleId = $user->role_id;
        $this->roleName = strtolower((string) (
            $user->role_id !== null
                ? Role::withoutGlobalScopes()->whereKey($user->role_id)->value('name')
                : null
        ));

        return $this->roleName;
    }

    public function branchId(): ?int
    {
        if ($this->branchId !== null) {
            return $this->branchId;
        }

        $branchId = Auth::user()?->business_branch_id;

        return $branchId !== null ? (int) $branchId : null;
    }

    public function isSiteAdmin(): bool
    {
        return $this->roleNameFor(Auth::user()) === 'siteadmin';
    }

    public function isPlatformOperator(): bool
    {
        return in_array($this->roleNameFor(Auth::user()), RolePermissions::PLATFORM_OPERATOR_ROLES, true);
    }

    public function hasBusiness(): bool
    {
        return $this->businessId !== null;
    }

    public function set(?int $businessId, ?int $branchId = null): void
    {
        $this->businessId = $businessId;
        $this->branchId = $branchId;
    }

    /**
     * Drop the context. Called between queued jobs so a long-lived worker never carries
     * one tenant's context into the next job.
     */
    public function clear(): void
    {
        $this->businessId = null;
        $this->branchId = null;
        $this->roleNameUserId = null;
        $this->roleNameRoleId = null;
        $this->roleName = '';
    }
}
