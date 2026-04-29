<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends BaseModel
{

    protected $fillable = [
        'business_id',
        'product_id',
        'type', // in, out, adjustment
        'quantity',
        'reference_type',
        'reference_id',
        'notes',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the parent reference model (Purchase, Sale, etc.).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}