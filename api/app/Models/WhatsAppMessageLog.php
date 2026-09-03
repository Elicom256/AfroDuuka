<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessageLog extends BaseModel
{
    protected $fillable = [
        'business_id',
        'template_id',
        'recipient',
        'channel',
        'message_body',
        'variables',
        'status',
        'provider_response',
        'error_code',
        'sent_at',
        'delivered_at',
        'read_at',
        'dedupe_key',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'provider_response' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsAppTemplate::class, 'template_id');
    }
}
