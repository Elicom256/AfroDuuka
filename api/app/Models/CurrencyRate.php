<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks exchange rates for multi-currency support.
 * Allows a business to convert between UGX, USD, KES, TZS etc.
 */
class CurrencyRate extends BaseModel
{
    protected $fillable = [
        'business_id',
        'base_currency',
        'target_currency',
        'rate',
        'source',
        'valid_from',
        'valid_to',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:6',
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
