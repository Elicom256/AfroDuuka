<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_branch_id',
        'tax_category_id',
        'amount',
        'payment_date',
        'tax_period_start',
        'tax_period_end',
        'reference',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'tax_period_start' => 'date',
        'tax_period_end' => 'date',
    ];

    public function businessBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class);
    }

    public function taxCategory(): BelongsTo
    {
        return $this->belongsTo(TaxCategory::class);
    }
}