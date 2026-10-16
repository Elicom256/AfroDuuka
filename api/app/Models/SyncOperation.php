<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per accepted sync operation, unique per (business_id, operation_id).
 *
 * See the create_sync_operations migration for the crash-consistency contract:
 * `processing` means nothing has been durably applied, `completed` means the
 * business effects and this row's completion committed together, `failed` means
 * the business transaction rolled back and the operation may be retried.
 */
class SyncOperation extends BaseModel
{
    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    /**
     * A `processing` claim older than this is considered abandoned (process crash
     * between claim and business transaction) and may be re-claimed. The business
     * effects of an interrupted claim were rolled back with their transaction, so
     * re-running is safe.
     */
    public const STALE_MINUTES = 5;

    protected $fillable = [
        'business_id',
        'user_id',
        'operation_id',
        'type',
        'status',
        'server_resource_type',
        'server_resource_id',
        'result',
        'error_code',
        'error_message',
        'attempts',
        'processed_at',
        'client_created_at',
    ];

    protected $casts = [
        'server_resource_id' => 'integer',
        'result' => 'array',
        'attempts' => 'integer',
        'processed_at' => 'datetime',
        'client_created_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * A claim that outlived its process. Safe to re-run only because the completion
     * marker commits with the business effects: stale + processing implies the
     * effects rolled back.
     */
    public function isStale(): bool
    {
        return $this->status === self::STATUS_PROCESSING
            && $this->updated_at !== null
            && $this->updated_at->lt(now()->subMinutes(self::STALE_MINUTES));
    }
}
