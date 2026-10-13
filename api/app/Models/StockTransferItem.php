<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransferItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'stock_transfer_id',
        'product_id',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
