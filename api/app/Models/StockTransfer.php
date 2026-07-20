<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

/**
 * Tracks stock movement between branches for inter-branch logistics.
 * Status flow: draft -> in_transit -> received -> cancelled.
 */
class StockTransfer extends BaseModel
{
    use LogsActivity;

    protected $fillable = [
        'business_id',
        'from_branch_id',
        'to_branch_id',
        'status',
        'transferred_by',
        'received_by',
        'dispatched_at',
        'received_at',
        'notes',
        'transport_cost',
    ];

    protected function casts(): array
    {
        return [
            'dispatched_at' => 'datetime',
            'received_at' => 'datetime',
            'transport_cost' => 'decimal:2',
        ];
    }

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class, 'from_branch_id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class, 'to_branch_id');
    }

    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }
}
