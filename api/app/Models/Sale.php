<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
// receipt / invoice like
class Sale extends BaseModel
{

    protected $fillable = [ 'business_id', 'total_amount', 'status' ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}