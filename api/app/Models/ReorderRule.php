<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReorderRule extends BaseModel
{
    protected $fillable = [
        'business_id',
        'business_branch_id',
        'product_id',
        'reorder_quantity',
        'preferred_supplier_id',
        'auto_approve',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'auto_approve' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function businessBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function preferredSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'preferred_supplier_id');
    }
}
