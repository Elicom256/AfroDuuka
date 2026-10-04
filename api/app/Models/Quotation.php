<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends BaseModel
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'business_id',
        'business_branch_id',
        'user_id',
        'customer_id',
        'quotation_number',
        'status',
        'valid_until',
        'currency',
        'subtotal',
        'tax_amount',
        'discount',
        'total_amount',
        'notes',
        'terms',
        'accepted_order_id',
    ];

    protected $casts = [
        'valid_until' => 'date:Y-m-d',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function acceptedOrder(): BelongsTo
    {
        return $this->belongsTo(SaleOrder::class, 'accepted_order_id');
    }

    public function businessBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class);
    }
}
