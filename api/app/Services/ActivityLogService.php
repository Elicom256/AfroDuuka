<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ActivityLogService
{
    /**
     * Records a manual activity entry.
     *
     * Causer, tenancy, IP and user agent are stamped by the model's creating
     * hook, so callers only describe what happened.
     *
     * Best-effort, deliberately: a controller calls this after the business write
     * has already been committed, so letting an insert failure escape would turn a
     * successful approval or payment into a 500. The failure is reported instead.
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

        try {
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
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
