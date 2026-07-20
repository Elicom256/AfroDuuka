<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class Product extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'business_branch_id',
        'product_category_id',
        'name',
        'sku',
        'barcode',
        'quantity',
        'cost_price',
        'price',
        'markup_percentage',
        'reorder_level',
        'description',
        'emoji',
        'status',
        'last_sold_at',
        'expiry_date',
    ];

    protected $casts = [
        'last_sold_at' => 'datetime',
        'expiry_date' => 'date',
        'track_serial' => 'boolean',
        'markup_percentage' => 'decimal:2',
    ];

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function businessBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class);
    }
}
