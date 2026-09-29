<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public function activity(string $logName, string $description)
    {
        $user = Auth::user();
        return ActivityLog::create([
            'log_name' => $logName,
            'description' => $description,
            'causer_type' => get_class($user),
            'causer_id' => $user->id,
            'business_id' => $user->business_id,
            'business_branch_id' => $user->business_branch_id,
        ]);
    }
}