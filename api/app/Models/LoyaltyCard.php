<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

/**
 * Links a customer to a loyalty program with their points balance and tier.
 */
class LoyaltyCard extends BaseModel
{
    use LogsActivity;

    protected $fillable = [
        'loyalty_program_id',
        'business_id',
        'customer_id',
        'points_balance',
        'tier',
        'issued_at',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'points_balance' => 'decimal:2',
            'issued_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function loyaltyProgram(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }
}
