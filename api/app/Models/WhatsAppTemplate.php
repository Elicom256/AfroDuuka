<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppTemplate extends BaseModel
{
    protected $fillable = [
        'business_id',
        'name',
        'category',
        'locale',
        'body',
        'variables',
        'status',
        'provider_name',
        'language_code',
        'template_category',
        'template_status',
        'parameter_format',
        'is_mandatory',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_mandatory' => 'boolean',
        ];
    }

    /**
     * A notification is only dispatched when Meta has approved the template. Owners may
     * edit the body wording; provider_name and language_code are not theirs to change.
     */
    public function isApproved(): bool
    {
        return $this->template_status === 'APPROVED';
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
