<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItem extends Model
{
    protected $fillable = [
        'sale_return_id',
        'sale_item_id',
        'quantity',
        'subtotal',
        'condition',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
    ];

    protected $appends = ['unit_price', 'product_id'];

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function getUnitPriceAttribute(): ?string
    {
        return $this->saleItem?->unit_price;
    }

    public function getProductIdAttribute()
    {
        return $this->saleItem?->product_id;
    }
}
