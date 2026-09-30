<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    /**
     * Records a manual activity entry.
     *
     * Causer, tenancy, IP and user agent are stamped by the model's creating
     * hook, so callers only describe what happened.
     *
     * @param  array<string, mixed>  $properties
     */
    public function activity(
        string $logName,
        string $description,
        array $properties = [],
        ?Model $subject = null,
        ?string $event = null,
    ): ?ActivityLog {
        $user = Auth::user();

        return ActivityLog::create([
            'log_name' => $logName,
            'event' => $event ?? $logName,
            'description' => $description,
            'causer_type' => $user?->getMorphClass(),
            'causer_id' => $user?->getKey(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
        ]);
    }
}
