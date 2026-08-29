<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class ProductAudit extends BaseModel
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'business_id',
        'business_branch_id',
        'audit_number',
        'audit_date',
        'status',
        'notes',
        'performed_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'audit_date' => 'date',
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

    public function items(): HasMany
    {
        return $this->hasMany(ProductAuditItem::class);
    }
}
