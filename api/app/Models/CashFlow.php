<?php

namespace App\Models;

use App\Enums\CashFlowDirection;
use App\Enums\CashFlowType;
use App\Traits\LogsActivity;
use Database\Factories\CashFlowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashFlow extends BaseModel
{
    /** @use HasFactory<CashFlowFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_code',
        'type',
        'amount',
        'currency',
        'business_id',
        'business_branch_id',
        'customer_id',
        'supplier_id',
        'sale_id',
        'purchase_id',
        'stock_transfer_id',
        'sale_return_id',
        'purchase_return_id',
        'expense_id',
        'description',
        'notes',
        'direction',
        'category',
        'payment_method',
        'payment_method_id',
        'reference',
        'status',
        'transaction_date',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'status' => 'string',
        // type and direction are deliberately NOT enum-cast. `type` is a plain string
        // column, the reports aggregate on it in raw SQL, and casting would change what
        // every API response and assertSame() in the suite reads. The enums own the list
        // of legal values and the sign mapping; they are resolved here on demand instead.
    ];

    /**
     * Relationships
     */

    // Business
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    // Branch
    public function branch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class, 'business_branch_id');
    }

    // Customer (for sales)
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Supplier (for purchases)
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    // Link to Sale (if it's from a sale)
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    // Link to Purchase (if it's from a purchase)
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    // Who recorded this transaction
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Link to Stock Transfer
    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    // Link to Sale Return
    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class, 'sale_return_id');
    }

    // Link to Purchase Return
    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    // Link to Expense
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    /**
     * Scopes
     */
    public function scopeSales($query)
    {
        return $query->where('type', 'sale');
    }

    public function scopePurchases($query)
    {
        return $query->where('type', 'purchase');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Signed effect of this row on the cash balance.
     *
     * `direction` is authoritative when present, because a manual adjustment has no
     * type that implies a sign. Types that do imply one are the fallback.
     *
     * A row that resolves to neither contributes nothing rather than being guessed at.
     * The adjustment endpoint requires a direction, so that only applies to rows
     * written before the requirement existed.
     */
    public function cashEffect(): float
    {
        if ($this->direction !== null) {
            $sign = CashFlowDirection::tryFrom($this->direction)?->sign() ?? 0;

            return $sign * (float) $this->amount;
        }

        // No direction on the row, so the type decides. Adjustment resolves to null here
        // rather than being guessed at, which is what keeps a signless adjustment inert
        // instead of silently moving the balance in some arbitrary direction.
        $sign = CashFlowType::tryFrom($this->type)?->sign() ?? 0;

        return $sign * (float) $this->amount;
    }

    /**
     * Accessors (Optional but useful)
     */
    public function getIsInflowAttribute(): bool
    {
        return $this->cashEffect() > 0;
    }

    public function getIsOutflowAttribute(): bool
    {
        return $this->cashEffect() < 0;
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'product_sales' => 'Product Sales',
            'product_purchases' => 'Product Purchases',
            'raw_materials' => 'Raw Materials',
            'rent' => 'Rent',
            'worker_payments' => 'Worker Payments',
            'stock_transfer' => 'Stock Transfer',
            'expenses' => 'Expenses',
            default => ucfirst(str_replace('_', ' ', $this->category ?? 'N/A')),
        };
    }
}
