<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppConfig extends BaseModel
{
    /**
     * Placeholder returned in place of the stored secrets. The settings screen keeps a
     * write-only input for these fields, so a masked round-trip means "leave unchanged".
     */
    public const SECRET_MASK = '********';

    /**
     * Never serialise the provider credentials, even if a controller returns the model
     * directly. Returning a model with these exposed leaked them to any authenticated
     * user who could call the index endpoint.
     */
    protected $hidden = [
        'access_token',
        'webhook_verify_token',
    ];

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
            'access_token' => 'encrypted',
            'webhook_verify_token' => 'encrypted',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
