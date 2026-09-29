<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isExecutive = strtolower($user->role->name) === 'executive';

        $query = ActivityLog::query()
            ->with(['causer', 'subject'])
            ->latest();

        if ($isExecutive) {
            $query->where('business_id', $user->business_id);
        } else {
            $query->where('causer_id', $user->id);
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'message' => 'Activity logs fetched',
            'data' => $logs,
        ]);
    }

    public function show(ActivityLog $activityLog)
    {
        $user = Auth::user();
        $isExecutive = strtolower($user->role->name) === 'executive';

        if (!$isExecutive && $activityLog->causer_id !== $user->id) {
            abort(403, 'You do not have access to this log entry.');
        }

        if ($isExecutive && $activityLog->business_id !== $user->business_id) {
            abort(403, 'You do not have access to this log entry.');
        }

        return response()->json([
            'message' => 'Activity log fetched',
            'data' => $activityLog->load(['causer', 'subject']),
        ]);
    }

    public function destroy(ActivityLog $activityLog)
    {
        $user = Auth::user();
        $isExecutive = strtolower($user->role->name) === 'executive';

        if (!$isExecutive && $activityLog->causer_id !== $user->id) {
            abort(403, 'You do not have access to this log entry.');
        }

        if ($isExecutive && $activityLog->business_id !== $user->business_id) {
            abort(403, 'You do not have access to this log entry.');
        }

        $activityLog->delete();

        return response()->json([
            'message' => 'Activity log deleted',
        ]);
    }
}
