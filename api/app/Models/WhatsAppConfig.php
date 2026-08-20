<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppConfig extends BaseModel
{
    protected $fillable = [
        'business_id',
        'provider',
        'business_phone',
        'phone_number_id',
        'access_token',
        'webhook_verify_token',
        'is_active',
        'message_template',
        'welcome_message',
        'last_webhook_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_webhook_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
