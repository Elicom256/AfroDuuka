<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Individual line item in a stock transfer.
 * Tracks expected vs received quantities per product.
 */
class StockTransferItem extends BaseModel
{
    protected $fillable = [
        'stock_transfer_id',
        'business_branch_product_id',
        'quantity_expected',
        'quantity_received',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity_expected' => 'integer',
            'quantity_received' => 'integer',
        ];
    }

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function businessBranchProduct(): BelongsTo
    {
        return $this->belongsTo(BusinessBranchProduct::class);
    }
}
