<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends BaseModel
{

    protected $fillable = [
        'business_id',
        'business_branch_id',
        'business_branch_product_id',
        'type', // in, out, adjustment
        'quantity',
        'reference_type',
        'reference_id',
        'notes',
    ];

    /**
     * The branch-level product (inventory item) this movement tracks.
     */
    public function businessBranchProduct(): BelongsTo
    {
        return $this->belongsTo(BusinessBranchProduct::class);
    }

    /**
     * Shortcut to the base product via the branch product pivot.
     */
    public function product()
    {
        return $this->businessBranchProduct->product();
    }

    /**
     * Get the parent reference model (Purchase, Sale, etc.).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}