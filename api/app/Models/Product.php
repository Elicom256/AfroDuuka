<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

class Product extends BaseModel
{
    use HasFactory, LogsActivity;

    /**
     * Stock alert state, so a re-check at an unchanged quantity stays silent.
     * products.business_branch_id is NOT NULL, so this state is inherently per-branch.
     */
    public const ALERT_OK = 'ok';

    public const ALERT_LOW_STOCK_FIRED = 'low_stock_fired';

    public const ALERT_OUT_OF_STOCK_FIRED = 'out_of_stock_fired';

    public const ALERT_SUPPRESSED = 'suppressed';

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
        'alert_state',
        'alert_episode',
    ];

    protected $casts = [
        'last_sold_at' => 'datetime',
        'expiry_date' => 'date',

        'is_tax_inclusive' => 'boolean',

        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'alert_episode' => 'integer',
    ];

    protected $appends = [
        'markup_percentage',
        'cover_url',
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

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * URL of the first image attachment (used as the product cover),
     * or null when the product has no image yet.
     */
    public function getCoverUrlAttribute(): ?string
    {
        $cover = $this->attachments->firstWhere('kind', 'image');

        return $cover ? Storage::disk($cover->disk ?: 'public')->url($cover->path) : null;
    }

    public function businessBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class);
    }

    public function taxCategory(): BelongsTo
    {
        return $this->belongsTo(TaxCategory::class);
    }

    /**
     * Quantity that can still be sold/allocated: on-hand minus what is already
     * reserved on non-cancelled sale orders (proposal.md step 2 allocation).
     */
    public function availableQuantity(): int
    {
        $reserved = SaleOrderItem::query()
            ->join('sale_orders', 'sale_orders.id', '=', 'sale_order_items.sale_order_id')
            ->where('sale_order_items.product_id', $this->getKey())
            ->where('sale_orders.status', '!=', 'cancelled')
            ->sum('sale_order_items.allocated_qty');

        return max(0, (int) $this->quantity - (int) $reserved);
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
