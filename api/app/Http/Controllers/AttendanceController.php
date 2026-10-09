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
        $query = Attendance::with(['worker.user.businessBranch']);

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
        $workerIds = collect($request->validated()['attendances'])->pluck('worker_id');

        // Scoped lookup: only workers the current user may touch (L1 on Worker).
        // Read through `user.business_branch_id`, not a column on workers — that column
        // is no longer mass-assignable, so it is NULL on every row and this used to
        // return an empty map, silently falling through to the caller's own branch for
        // every worker in the batch.
        $branchById = Worker::with('user')
            ->whereIn('id', $workerIds)
            ->get()
            ->mapWithKeys(fn (Worker $worker) => [$worker->id => $worker->user?->business_branch_id]);

        $records = collect($request->validated()['attendances'])
            ->map(function ($attendance) use ($user, $branchById) {
                $workerId = $attendance['worker_id'];
                $branchId = $branchById->get($workerId)
                    ?? $attendance['business_branch_id']
                    ?? $user->business_branch_id;

                if (! $branchId) {
                    abort(422, "Worker {$workerId} is not accessible.");
                }

                return [
                    ...$attendance,
                    'business_branch_id' => $branchId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->toArray();

        Attendance::insert($records);
        $this->activity_log->activity('Recorded Employee Attendance', count($records).' '.'employees have been recorded');

        return response()->json([
            'message' => count($records).' attendance records saved successfully',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Attendance $attendance)
    {
        $attendance->load('worker.user');

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
