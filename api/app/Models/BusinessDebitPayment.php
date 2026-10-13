<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessDebitPayment extends BaseModel
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'business_debit_id',
        'business_branch_id',
        'amount',
        'payment_date',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function businessDebit(): BelongsTo
    {
        return $this->belongsTo(BusinessDebit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
