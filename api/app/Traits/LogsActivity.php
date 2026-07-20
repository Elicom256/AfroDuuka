<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $model->logActivityEvent('created');
        });

        static::updated(function ($model) {
            $model->logActivityEvent('updated');
        });

        static::deleted(function ($model) {
            $model->logActivityEvent('deleted');
        });
    }

    protected function logActivityEvent(string $event): void
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $modelName = class_basename($this);
        $description = ucfirst($event) . ' ' . $modelName . ' #' . $this->getKey();

        ActivityLog::create([
            'user_id' => $user->id,
            'business_id' => $user->business_id,
            'business_branch_id' => $user->business_branch_id,
            'action' => strtoupper($modelName) . '_' . strtoupper($event),
            'description' => $description,
            'subject_type' => get_class($this),
            'subject_id' => $this->getKey(),
        ]);
    }
}
