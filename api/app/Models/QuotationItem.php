<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'product_id',
        'product_name',
        'sku',
        'quantity',
        'unit_price',
        'discount',
        'tax_rate',
        'is_tax_inclusive',
        'taxable_amount',
        'tax_amount',
        'subtotal',
    ];

    protected $casts = [
        'unit_price'     => 'decimal:2',
        'discount'       => 'decimal:2',
        'tax_rate'       => 'decimal:4',
        'is_tax_inclusive' => 'boolean',
        'taxable_amount' => 'decimal:2',
        'tax_amount'     => 'decimal:2',
        'subtotal'       => 'decimal:2',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}