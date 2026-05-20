<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'business_branch_product_id',
        'quantity',
        'cost_price',
        'subtotal',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function businessBranchProduct(): BelongsTo
    {
        return $this->belongsTo(BusinessBranchProduct::class);
    }
}
