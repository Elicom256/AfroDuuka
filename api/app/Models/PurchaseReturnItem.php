<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnItem extends Model
{
    protected $fillable = [
        'purchase_return_id',
        'purchase_item_id',
        'quantity',
        'subtotal',
        'condition',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
    ];

    protected $appends = ['cost_price', 'product_id'];

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function getCostPriceAttribute(): ?string
    {
        return $this->purchaseItem?->cost_price;
    }

    public function getProductIdAttribute()
    {
        return $this->purchaseItem?->product_id;
    }
}
