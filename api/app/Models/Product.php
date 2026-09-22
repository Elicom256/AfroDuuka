<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'business_branch_id',
        'product_category_id',
        'tax_category_id',

        'name',
        'sku',
        'barcode',

        'quantity',

        // Pricing
        'cost_price',
        'selling_price',
        'is_tax_inclusive',

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

        'is_tax_inclusive' => 'boolean',

        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    protected $appends = [
        'markup_percentage',
    ];

    /**
     * Transient price change reason consumed by PriceHistoryObserver.
     * Declared as a real property so it is never persisted to the database.
     */
    public ?string $priceChangeReason = null;

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

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

    public function taxCategory(): BelongsTo
    {
        return $this->belongsTo(TaxCategory::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate the markup percentage from cost and selling price.
     *
     * Markup is intentionally NOT stored in the database because it is
     * derived from the two source values.
     */
    public function getMarkupPercentageAttribute(): ?float
    {
        if (
            $this->cost_price === null ||
            (float) $this->cost_price <= 0 ||
            $this->selling_price === null
        ) {
            return null;
        }

        return round(
            (((float) $this->selling_price - (float) $this->cost_price)
                / (float) $this->cost_price) * 100,
            2
        );
    }
}