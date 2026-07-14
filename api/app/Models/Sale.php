<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
// receipt / invoice like
class Sale extends BaseModel
{

    protected $fillable = [ 'business_branch_id', 'customer_id', 'total_amount', 'status', 'note'];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
    public function businessBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class);
    }

     public function salePayment(): BelongsTo
    {
        return $this->belongsTo(SalePayment::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }
}