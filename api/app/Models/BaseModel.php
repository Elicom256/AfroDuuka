<?php

namespace App\Models;

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
            if (
                static::tenantTableHasColumn('business_id')
                && Auth::check()
                && Auth::user()?->business_id
            ) {
                $builder->where('business_id', Auth::user()->business_id);
            }
        });

        static::addGlobalScope('branch', function ($builder) {
            if (static::tenantTableHasColumn('business_branch_id')) {
                EffectiveBranchScope::apply($builder);
            }
        });

        static::creating(function ($model) {
            if (
                static::tenantTableHasColumn('business_id')
                && Auth::check()
                && Auth::user()?->business_id
                && ! isset($model->business_id)
            ) {
                $model->business_id = Auth::user()->business_id;
            }

            if (
                static::tenantTableHasColumn('business_branch_id')
                && Auth::check()
                && Auth::user()?->business_branch_id
                && ! isset($model->business_branch_id)
            ) {
                $model->business_branch_id = Auth::user()->business_branch_id;
            }
        });
    }
}
