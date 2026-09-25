<?php

namespace App\Support\Tenant;

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
 *     BusinessContext::run($businessId, fn () => ...);
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

        return $user?->business_id !== null ? (int) $user->business_id : null;
    }

    public function branchId(): ?int
    {
        if ($this->branchId !== null) {
            return $this->branchId;
        }

        $branchId = Auth::user()?->business_branch_id;

        return $branchId !== null ? (int) $branchId : null;
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
    }
}
