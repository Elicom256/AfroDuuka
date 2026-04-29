<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'business_id', 
        'name', 
        'email', 
        'phone', 
        'address', 
        'status'
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
}
