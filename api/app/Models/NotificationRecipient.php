<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationRecipient extends BaseModel
{
    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const CHANNEL_EMAIL = 'email';

    public const LABEL_OWNER = 'owner';

    public const LABEL_ADMIN = 'admin';

    public const LABEL_MANAGER = 'manager';

    public const LABEL_BRANCH_MANAGER = 'branch_manager';

    public const LABEL_CUSTOM = 'custom';

    /**
     * Mirrors the column defaults so a freshly built model carries them in memory.
     *
     * Without this, create() leaves these attributes null until the model is
     * refreshed, and isActive() would answer false for a row the database says is
     * active — the resolver would then skip a valid recipient and log it as
     * no_recipient. Database defaults alone are not enough for in-memory reads.
     */
    protected $attributes = [
        'label' => self::LABEL_OWNER,
        'is_active' => true,
    ];

    protected $fillable = [
        'business_id',
        'business_branch_id',
        'user_id',
        'label',
        'channel',
        'address',
        'categories',
        'is_active',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'is_active' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class, 'business_branch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class, 'recipient_id');
    }

    public function scopeForChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    /**
     * Restrict to recipients that may actually be sent to.
     *
     * is_active alone is not enough, because verified_at is null until the first
     * successful send. Selecting on both here keeps the resolver from having to
     * remember to filter, and keeps the "no recipient" delivery log honest.
     */
    public function scopeDeliverable(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNotNull('verified_at');
    }

    /**
     * Per-category preference. An empty or absent list means "no preferences set",
     * i.e. everything is allowed; a populated list is an allow-list.
     *
     * Mirrors NotificationSubscription::isSubscribedTo on purpose: the same category
     * name is checked against both when a notification fans out to two channels, and
     * having the two disagree would let someone opt out of email while still
     * receiving the WhatsApp of the same event.
     *
     * Mandatory categories bypass this entirely at the resolver, not here.
     */
    public function canReceive(string $category): bool
    {
        $categories = $this->categories ?? [];

        return $categories === [] || in_array($category, $categories, true);
    }

    /**
     * An unverified recipient is treated as absent by the resolver. Verification happens
     * on the first successful send, not at signup.
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active && $this->isVerified();
    }
}
