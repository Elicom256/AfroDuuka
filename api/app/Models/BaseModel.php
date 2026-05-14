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

        // scope by business_branch_id
        // static::addGlobalScope("branch", function ($builder){
        //     if(Auth::check() && Auth::user()->business_branch_id){
        //         $builder->where("business_branch_id", Auth::user()->business_branch_id);
        //     }
        // });

                // Auto-fill business_id
        static::creating(function ($model) {
            if (Auth::check() && Auth::user()?->business_id && !isset($model->business_id)) {
                $model->business_id = Auth::user()->business_id;
            }
        });

            // Auto-fill branch_id
        // static::creating(function ($model) {
        //     if (Auth::check() && Auth::user()?->business_branch_id && !isset($model->business_branch_id)) {
        //         $model->business_branch_id = Auth::user()->business_branch_id;
        //     }
        // });
    }
}