<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
// receipt / invoice like
class Sale extends BaseModel
{

    protected $fillable = [
        'business_id',
        'total_amount',
        'sold_at',
        'status',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'sold_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}