<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Defines a customer loyalty program (points, stamps, or tiered).
 * Each business can have one active program.
 */
class LoyaltyProgram extends BaseModel
{
    protected $fillable = [
        'business_id',
        'name',
        'type',
        'points_per_currency',
        'redemption_rate',
        'expiry_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'points_per_currency' => 'decimal:2',
            'redemption_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function cards(): HasMany
    {
        return $this->hasMany(LoyaltyCard::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(LoyaltyReward::class);
    }
}
