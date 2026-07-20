<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class CashFlow extends BaseModel
{
    /** @use HasFactory<\Database\Factories\CashFlowFactory> */
    use HasFactory, SoftDeletes, LogsActivity;

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
        'tax_payment_id',
        'stock_transfer_id',
        'sale_return_id',
        'purchase_return_id',
        'expense_id',
        'description',
        'category',
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

    // Link to Tax Payment
    public function taxPayment(): BelongsTo
    {
        return $this->belongsTo(BusinessTaxPayment::class, 'tax_payment_id');
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
     * Accessors (Optional but useful)
     */
    public function getIsInflowAttribute(): bool
    {
        return in_array($this->type, ['sale', 'payment_in', 'refund']);
    }

    public function getIsOutflowAttribute(): bool
    {
        return in_array($this->type, ['purchase', 'expense', 'payment_out']);
    }

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'product_sales'     => 'Product Sales',
            'product_purchases' => 'Product Purchases',
            'raw_materials'     => 'Raw Materials',
            'rent'              => 'Rent',
            'worker_payments'   => 'Worker Payments',
            'tax_payments'      => 'Tax Payments',
            'stock_transfer'    => 'Stock Transfer',
            'expenses'          => 'Expenses',
            default             => ucfirst(str_replace('_', ' ', $this->category ?? 'N/A')),
        };
    }
}