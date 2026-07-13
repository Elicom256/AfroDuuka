<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends BaseModel
{
    protected $fillable = [
        'business_branch_id',
        'reason',
        'notes',
        'refund_amount',
        'restock',
        'processed_by',
        'status',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'restock' => 'boolean',
    ];

    public function saleReturnItems(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function businessBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class);
    }

    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
