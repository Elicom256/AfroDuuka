<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends BaseModel
{
    use LogsActivity;

    protected $fillable = ['business_branch_id', 'supplier_id', 'total_amount', 'status', 'received_at', 'received_by', 'note'];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'received_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function businessBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class);
    }
}
