<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAuditItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_audit_id',
        'product_id',
        'system_quantity',
        'counted_quantity',
        'difference',
        'adjustment_quantity',
        'notes',
    ];

    protected $casts = [
        'system_quantity' => 'integer',
        'counted_quantity' => 'integer',
        'difference' => 'integer',
        'adjustment_quantity' => 'integer',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(ProductAudit::class, 'product_audit_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
