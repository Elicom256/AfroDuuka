<?php

namespace App\Models;

/**
 * A redeemable reward in the loyalty program (discount, free item, etc.).
 */
class LoyaltyReward extends BaseModel
{
    protected $fillable = [
        'business_id',
        'name',
        'description',
        'points_required',
        'image_url',
        'stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'points_required' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
