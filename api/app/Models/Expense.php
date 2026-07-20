<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class Expense extends BaseModel
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'expense_category_id',
        'amount',
        'business_id',
        'business_branch_id',
        'vendor',
        'description',
        'receipt',
        'is_recurring',
        'payment_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'is_recurring' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function businessBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class, 'business_branch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cashFlow(): BelongsTo
    {
        return $this->belongsTo(CashFlow::class, 'expense_id');
    }
}
