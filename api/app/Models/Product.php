<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'category_id',
        'name',
        'sku',
        'barcode',
        'price',
        'cost_price',
        'quantity',
        'minimum_stock',
        'status',
        'description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'status' => 'boolean',
        'quantity' => 'integer',
        'minimum_stock' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}