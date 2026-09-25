<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The single cross-channel record of whether a notification was actually delivered.
 *
 * This is the only place that can answer "did they get it?" for both email and
 * WhatsApp. whats_app_message_logs is a deprecated read-only mirror of the WhatsApp
 * subset and is retained for one release only.
 */
class NotificationDelivery extends BaseModel
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_READ = 'read';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SUPPRESSED = 'suppressed';

    public const REASON_NO_RECIPIENT = 'no_recipient';

    public const REASON_TEMPLATE_NOT_APPROVED = 'template_not_approved';

    public const REASON_OPTED_OUT = 'opted_out';

    public const REASON_BAD_ADDRESS = 'bad_address';

    public const REASON_ADDRESS_SUPPRESSED = 'address_suppressed';

    public const REASON_DUPLICATE = 'duplicate';

    protected $fillable = [
        'business_id',
        'business_branch_id',
        'channel',
        'category',
        'type',
        'template_key',
        'recipient_id',
        'recipient_address',
        'status',
        'provider',
        'provider_message_id',
        'attempt_count',
        'last_attempt_at',
        'payload',
        'error_code',
        'error_message',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'suppressed_at',
        'dedupe_key',
        'meta_template_name',
        'meta_template_language',
        'is_mandatory',
        'suppressed_reason',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'is_mandatory' => 'boolean',
            'attempt_count' => 'integer',
            'last_attempt_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
            'suppressed_at' => 'datetime',
        ];
    }

    /**
     * The values this delivery was built from.
     *
     * The dispatcher stores the rendered {{token}} parameters for WhatsApp and the raw
     * values for email, in the same column, because a delivery row is a record of what
     * was attempted rather than a render. The email channel reads this to build its
     * Mailable; the WhatsApp channel does not need it because the message body is
     * already rendered by the time it is queued.
     *
     * @return array<string, mixed>
     */
    public function values(): array
    {
        return $this->payload ?? [];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class, 'business_branch_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(NotificationRecipient::class, 'recipient_id');
    }

    public function scopeForProviderMessage(Builder $query, string $provider, string $messageId): Builder
    {
        return $query->where('provider', $provider)->where('provider_message_id', $messageId);
    }

    /**
     * Terminal states are never retried. A row left in `sending` is an ambiguous
     * timeout, not a failure: the request may have reached the provider, so it is the
     * webhook's job to resolve it rather than a retry that could double-send.
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_DELIVERED,
            self::STATUS_READ,
            self::STATUS_SUPPRESSED,
        ], true);
    }

    public function isAmbiguous(): bool
    {
        return $this->status === self::STATUS_SENDING;
    }

    /**
     * Record a send that was deliberately never attempted.
     *
     * Refuses to overwrite a delivery the provider already confirmed. A late-arriving
     * duplicate, or a webhook racing the resolver, must not downgrade a `delivered`
     * row to `suppressed` and make a real send look like it never happened.
     *
     * @return bool true if this call wrote the suppression
     */
    public function markSuppressed(string $reason): bool
    {
        if ($this->isTerminal()) {
            return false;
        }

        $this->forceFill([
            'status' => self::STATUS_SUPPRESSED,
            'suppressed_reason' => $reason,
            'suppressed_at' => now(),
        ])->save();

        return true;
    }

    /**
     * @return bool true if the row was in a state this transition is allowed from
     */
    public function markFailed(string $errorCode, string $errorMessage): bool
    {
        if (in_array($this->status, [
            self::STATUS_DELIVERED,
            self::STATUS_READ,
            self::STATUS_SUPPRESSED,
        ], true)) {
            return false;
        }

        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'error_code' => $errorCode,
            'error_message' => mb_substr($errorMessage, 0, 1000),
            'failed_at' => now(),
        ])->save();

        return true;
    }
}
