<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records points earned, spent, or adjusted on a loyalty card.
 */
class LoyaltyTransaction extends BaseModel
{
    protected $fillable = [
        'loyalty_card_id',
        'sale_id',
        'type',
        'points',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'decimal:2',
        ];
    }

    public function loyaltyCard(): BelongsTo
    {
        return $this->belongsTo(LoyaltyCard::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
