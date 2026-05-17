<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{

    protected $fillable = [ 'business_branch_id', 'supplier_id', 'total_amount', 'status', 'note'];

    protected $casts = [
        'total_amount' => 'decimal:2',
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
