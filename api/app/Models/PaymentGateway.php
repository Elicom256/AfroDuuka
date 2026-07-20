<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

/**
 * Stores payment provider credentials (MTN MoMo, Airtel Money, etc.)
 * Each business can configure their own gateway credentials.
 */
class PaymentGateway extends BaseModel
{
    use LogsActivity;

    protected $fillable = [
        'business_id',
        'provider',
        'api_key',
        'api_secret',
        'webhook_secret',
        'extra_config',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'extra_config' => 'json',
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
