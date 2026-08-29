<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class FinancialAudit extends BaseModel
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'business_id',
        'business_branch_id',
        'audit_number',
        'audit_date',
        'expected_balance',
        'actual_balance',
        'difference',
        'notes',
        'status',
        'performed_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'expected_balance' => 'decimal:2',
        'actual_balance' => 'decimal:2',
        'difference' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class, 'business_branch_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
