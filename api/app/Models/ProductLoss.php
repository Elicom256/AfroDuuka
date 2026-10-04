<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductLoss extends BaseModel
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'business_branch_id',
        'product_id',
        'stock_movement_id',
        'type',
        'quantity',
        'unit_cost',
        'total_loss',
        'reason',
        'loss_date',
        'reported_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'integer',
        'total_loss' => 'integer',
        'loss_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
