<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::query()
            ->with(['causer', 'subject'])
            ->latest();

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
        return response()->json([
            'message' => 'Activity log fetched',
            'data' => $activityLog->load(['causer', 'subject']),
        ]);
    }

    public function destroy(ActivityLog $activityLog)
    {
        $activityLog->delete();

        return response()->json([
            'message' => 'Activity log deleted',
        ]);
    }
}
