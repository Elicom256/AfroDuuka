<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class BusinessTaxPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'business_tax_payments';

    protected $fillable = [
        'business_branch_id',
        'business_tax_id',
        'amount',
        'paid_amount',
        'balance',
        'tax_period',
        'due_date',
        'payment_date',
        'paid_at',
        'status',
        'reference_number',
        'payment_method',
        'payment_metadata',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'paid_amount'      => 'decimal:2',
        'balance'          => 'decimal:2',
        'due_date'         => 'date',
        'payment_date'     => 'date',
        'paid_at'          => 'datetime',
        'payment_metadata' => 'array',
    ];

    // ==============================
    // Relationships
    // ==============================

    public function businessBranch()
    {
        return $this->belongsTo(BusinessBranch::class, 'business_branch_id');
    }

    public function businessTax()
    {
        return $this->belongsTo(BusinessTaxes::class, 'business_tax_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ==============================
    // Scopes
    // ==============================

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['unpaid', 'partial']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', Carbon::today())
                     ->whereNotIn('status', ['paid', 'waived']);
    }

    public function scopeForPeriod($query, string $period)
    {
        return $query->where('tax_period', $period);
    }

    public function scopeByBranch($query, int $branchId)
    {
        return $query->where('business_branch_id', $branchId);
    }

    // ==============================
    // Accessors & Mutators
    // ==============================

    protected static function booted()
    {
        static::saving(function ($payment) {
            // Auto-calculate balance
            $payment->balance = $payment->amount - ($payment->paid_amount ?? 0);

            // Only auto-calculate status if status was not explicitly set to a manual state
            // Manual statuses: overdue, waived, refunded
            // Auto-calculated statuses: unpaid, partial, paid
            $manualStatuses = ['overdue', 'waived', 'refunded'];
            $isManualOverride = in_array($payment->status, $manualStatuses);

            if (!$isManualOverride) {
                if ($payment->balance <= 0) {
                    $payment->status = 'paid';
                    $payment->payment_date = $payment->payment_date ?? now()->toDateString();
                } elseif ($payment->paid_amount > 0) {
                    $payment->status = 'partial';
                } else {
                    $payment->status = 'unpaid';
                }
            }
        });
    }

    // Optional: Human readable status
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'paid'     => 'Paid',
            'partial'  => 'Partially Paid',
            'overdue'  => 'Overdue',
            'waived'   => 'Waived',
            'refunded' => 'Refunded',
            default    => 'Unpaid',
        };
    }
}