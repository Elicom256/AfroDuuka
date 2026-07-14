<?php

/*-----------------------------------------------------------------------------------
 * Model: PriceHistory
 * -------------------------------
 * Each row records a single price change for a product.
 * Linked to the product via product_id.
 *---------------------------------------------------------------------------------*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'old_cost_price',
        'new_cost_price',
        'old_sale_price',
        'new_sale_price',
        'changed_by',
        'change_reason',
    ];

    protected $casts = [
        'old_cost_price' => 'decimal:2',
        'new_cost_price' => 'decimal:2',
        'old_sale_price' => 'decimal:2',
        'new_sale_price' => 'decimal:2',
    ];

    /**
     * The product whose price changed.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The user who made the price change.
     */
    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
