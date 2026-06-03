<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public function activity(string $action, string $description)
    {
        $user = Auth::user();
       return ActivityLog::create([
        "user_id" => $user->id,
        "business_id" => $user->business_id,
        "business_branch_id" => $user->business_branch_id,
        "action" => $action,
        "description" =>$description
        ]);
    }
}