<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class PurchaseReturn extends Model
{
    use LogsActivity;

    protected $fillable = [
        'business_branch_id',
        'supplier_id',
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

    public function purchaseReturnItems(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
