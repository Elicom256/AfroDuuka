<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BaseModel extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope('business', function ($builder) {
            if (Auth::check() && Auth::user()?->business_id) {
                $builder->where('business_id', Auth::user()->business_id);
            }
        });

        static::creating(function ($model) {
            if (Auth::check() && Auth::user()?->business_id && !isset($model->business_id)) {
                $model->business_id = Auth::user()->business_id;
            }
        });
    }
}