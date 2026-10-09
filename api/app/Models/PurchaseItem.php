<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A line on a purchase order.
 *
 * SoftDeletes because the migration has the column and the data is financial: removing a
 * line out of a purchase erases the cost it recorded, and the product's stock and the
 * supplier's history then disagree with it. Without the trait the column was written by
 * nothing and read by nothing, so a deleted line was indistinguishable from one that had
 * never existed — the opposite of what the schema implied.
 */
class PurchaseItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'quantity',
        'cost_price',
        'selling_price',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
