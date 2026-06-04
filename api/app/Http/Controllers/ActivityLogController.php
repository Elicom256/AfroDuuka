<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * GET /activity-logs
     */
    public function index(Request $request)
    {
        $query = ActivityLog::query()
            ->with(['user', 'business', 'branch'])
            ->latest();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        $total = (clone $query)->count() ?? 0;
        $logs = (clone $query)->paginate($request->get('per_page', 10));
        $distinct = (clone $query)->select("user_id")->distinct()->count() ?? 0;
        return response()->json([
            "message" => "logs fetched",
             "logs" => $logs,
             "total" => $total,
             "distinct" => $distinct
         ]);
    }

    /**
     * POST /activity-logs
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'business_id' => ['nullable', 'exists:businesses,id'],
            'business_branch_id' => ['nullable', 'exists:business_branches,id'],
            'action' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $log = ActivityLog::create($validated);

        return response()->json([
            'message' => 'Activity log created',
            'data' => $log->load(['user', 'business', 'branch']),
        ], 201);
    }

    /**
     * GET /activity-logs/{id}
     */
    public function show(string $id)
    {
        $log = ActivityLog::with(['user', 'business', 'branch'])->findOrFail($id);

        return response()->json($log);
    }

    /**
     * DELETE /activity-logs/{id}
     */
    public function destroy(string $id)
    {
        ActivityLog::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Activity log deleted'
        ]);
    }
}