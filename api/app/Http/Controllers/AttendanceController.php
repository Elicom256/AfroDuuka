<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $business_branch_id = Auth::user()->business_branch_id;
        $attendances = Attendance::with(["worker.user"])
                       ->whereHas("worker.user", function ($query) use($business_branch_id) {
                            $query->where("business_branch_id", $business_branch_id);
                       })
                       ->orderByDesc("created_at")
                       ->get();
        return response()->json(["message" => "Attendances fetched", "attendances" => $attendances]);             
    }

    /**
     * Store a newly created resource in storage.
     */
   public function store(StoreAttendanceRequest $request)
{
    $branchId = Auth::user()->business_branch_id;
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
