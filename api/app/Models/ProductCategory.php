<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class ProductCategory extends BaseModel
{
    use LogsActivity;

    protected $fillable = ['business_id', 'name', 'description', 'status'];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
