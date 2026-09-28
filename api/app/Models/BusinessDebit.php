<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessDebit extends BaseModel
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'business_branch_id',
        'supplier_id',
        'amount',
        'reference',
        'status',
        'description',
        'due_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BusinessDebitPayment::class);
    }

    public function amountPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function balance(): float
    {
        return round((float) $this->amount - $this->amountPaid(), 2);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && $this->balance() > 0;
    }

    public function getLifecycleStatusAttribute(): string
    {
        $balance = $this->balance();

        if ($balance <= 0) {
            return 'settled';
        }

        if ($this->is_overdue) {
            return 'overdue';
        }

        if ($this->amountPaid() > 0) {
            return 'partial';
        }

        return 'issued';
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->where('status', 'open');
    }
}
