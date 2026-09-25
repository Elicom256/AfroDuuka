<?php

namespace App\Models;

use App\Support\Tenant\BusinessContext;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class BaseModel extends Model
{
    protected static array $tenantColumnCache = [];

    protected static function tenantTableHasColumn(string $column): bool
    {
        $class = static::class;

        if (! isset(static::$tenantColumnCache[$class][$column])) {
            static::$tenantColumnCache[$class][$column] = Schema::hasColumn((new static)->getTable(), $column);
        }

        return static::$tenantColumnCache[$class][$column];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('business', function ($builder) {
            if (! static::tenantTableHasColumn('business_id')) {
                return;
            }

            // Fall back to BusinessContext when there is no authenticated user.
            // Previously this scope was gated on Auth::check() alone, so every queued
            // and scheduled job read across all tenants.
            $businessId = app(BusinessContext::class)->businessId()
                ?? (Auth::check() ? Auth::user()?->business_id : null);

            if ($businessId !== null) {
                $builder->where('business_id', $businessId);
            }
        });

        static::addGlobalScope('branch', function ($builder) {
            if (static::tenantTableHasColumn('business_branch_id')) {
                EffectiveBranchScope::apply($builder);
            }
        });

        static::creating(function ($model) {
            $user = Auth::user();

            // The context wins over the authenticated user so a job can create rows for
            // an explicit tenant even when a session happens to be present.
            $businessId = app(BusinessContext::class)->businessId() ?? $user?->business_id;
            $branchId = app(BusinessContext::class)->branchId() ?? $user?->business_branch_id;

            if (
                static::tenantTableHasColumn('business_id')
                && $businessId
                && ! isset($model->business_id)
            ) {
                $model->business_id = $businessId;
            }

            if (
                static::tenantTableHasColumn('business_branch_id')
                && $branchId
                && ! isset($model->business_branch_id)
            ) {
                $model->business_branch_id = $branchId;
            }
        });
    }
}
