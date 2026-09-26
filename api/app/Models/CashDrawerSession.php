<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashDrawerSession extends BaseModel
{
    protected $fillable = [
        'business_id',
        'business_branch_id',
        'opened_by',
        'closed_by',
        'opening_cash',
        'expected_cash',
        'counted_cash',
        'variance',
        'allowed_variance',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'opening_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'counted_cash' => 'decimal:2',
        'variance' => 'decimal:2',
        'allowed_variance' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class, 'business_branch_id');
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
