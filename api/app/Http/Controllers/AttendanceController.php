<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
     protected ActivityLogService $activity_log;
    public function __construct(ActivityLogService $activityLog)
    {
        $this->activity_log = $activityLog;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    $user = Auth::user();

    $query = Attendance::with(['worker.user.businessBranch'])
        ->whereHas('worker.user', function ($q) use ($user) {
            $q->where('business_branch_id', $user->business_branch_id)
              ->where('business_id', $user->business_id);
        });

    // stats (global, not paginated)
    $presentCount = (clone $query)
        ->where('status', 'present')
        ->count();

    $absentCount = (clone $query)
        ->where('status', 'absent')
        ->count();

    // paginated data
    $attendances = $query
        ->orderByDesc('created_at')
        ->paginate(10);

    return response()->json([
        'message' => 'Attendances fetched',
        'attendances' => $attendances,
        'presentCount' => $presentCount,
        'absentCount' => $absentCount,
    ]);
}
    /**
     * Store a newly created resource in storage.
     */
   public function store(StoreAttendanceRequest $request)
{
    $user = Auth::user();
    $branchId = $user->business_branch_id;
    $records = collect($request->validated()['attendances'])
        ->map(function ($attendance) use ($branchId) {
            return [
                ...$attendance,
                'business_branch_id' => $branchId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })
        ->toArray();

    Attendance::insert($records);
    $this->activity_log->activity("Recorded Employee Attendance", count($records) . " ". "employees have been recorded");
    return response()->json([
        'message' => count($records) . ' attendance records saved successfully'
    ]);
}

    /**
     * Display the specified resource.
     */
    public function show(Attendance $attendance)
    {
        $attendance->load("worker.user");
        return response()->json([
        'message' => 'Attendance fetched successfully!',
        'data' => $attendance,
    ]);
    }

    /**
     * Update the specified resource in storage.
     */
   public function update(UpdateAttendanceRequest $request, Attendance $attendance)
{
    $attendance->update($request->validated());

    return response()->json([
        'message' => 'Attendance updated successfully',
        'data' => $attendance->fresh(),
    ]);
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Attendance $attendance)
    {
        $attendance->delete();
        return response()->json([
        'message' => 'Attendance Deleted successfully!',
        'data' => $attendance,
    ]);
    }
}
