<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'business_branch_id',
        'action',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log($user, string $action, Model $subject, ?string $description = null, array $metadata = []): self
    {
        return self::create([
            'user_id' => $user->id,
            'business_id' => $user->business_id ?? null,
            'business_branch_id' => $user->business_branch_id ?? null,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->getKey(),
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
