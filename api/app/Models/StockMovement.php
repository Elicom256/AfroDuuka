<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Traits\LogsActivity;

class StockMovement extends BaseModel
{
    use LogsActivity;

    protected $fillable = [
        'business_id',
        'business_branch_id',
        'product_id',
        'movement_key',
        'type',
        'quantity',
        'reason',
        'reference_type',
        'reference_id',
        'notes',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
