<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRemunerationRequest;
use App\Http\Requests\UpdateEmployeeRemunerationRequest;
use App\Models\ActivityLog;
use App\Models\EmployeeRemuneration;
use App\Models\Worker;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EmployeeRemunerationController extends Controller
{
    protected ActivityLogService $activity_log;
    public function __construct(ActivityLogService $activityLog)
    {
        $this->activity_log = $activityLog;
    }
    public function index(): JsonResponse
{
    $user = Auth::user();

    $query = EmployeeRemuneration::with([
        'worker.user.businessBranch',
    ])
    ->whereHas('worker.user', function ($query) use ($user) {
        $query->where('business_branch_id', $user->business_branch_id)
            ->where('business_id', $user->business_id);
    });

    $totalPaid = (clone $query)
        ->where('status', 'paid')
        ->sum('amount');

    $employeeCount = (clone $query)
        ->distinct('worker_id')
        ->count('worker_id');

    $pending = (clone $query)
        ->where('status', 'pending')
        ->count();

    $remunerations = $query
        ->orderByDesc('payment_date')
        ->paginate(10);

    return response()->json([
        'message' => 'Fetched employee remunerations',
        'employee_remunerations' => $remunerations,
        'totalPaid' => $totalPaid,
        'employeeCount' => $employeeCount,
        'pending' => $pending,
    ]);
}

    public function store(StoreEmployeeRemunerationRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        $worker = Worker::with('user')->findOrFail($validated['worker_id']);
        if ($worker->user->business_id !== $user->business_id) {
            abort(403, 'Worker does not belong to your business');
        }

        $validated['business_id'] = $user->business_id;
        $validated['business_branch_id'] = $worker->business_branch_id;

        $remuneration = EmployeeRemuneration::create($validated);
        $employee =$worker->load("user");
        $this->activity_log->activity("Recorded Employee Remuneration", $employee->user->name ." ". "has been paid");

        return response()->json([
            'message' => 'Employee remuneration created successfully',
            'employee_remuneration' => $remuneration,
        ], 201);
    }

    public function show(EmployeeRemuneration $employeeRemuneration): JsonResponse
    {
        $employeeRemuneration->load(['worker.user', 'worker.businessBranch']);

        return response()->json([
            'message' => 'Fetched employee remuneration',
            'employee_remuneration' => $employeeRemuneration,
        ]);
    }

    public function update(UpdateEmployeeRemunerationRequest $request, EmployeeRemuneration $employeeRemuneration): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        if ($employeeRemuneration->business_id !== $user->business_id) {
            abort(403, 'You are not authorized to update this remuneration');
        }

        $employeeRemuneration->update($validated);

        ActivityLog::log(
            $user,
            'updated_employee_remuneration',
            $employeeRemuneration,
            sprintf('Updated remuneration record ID %s.', $employeeRemuneration->id),
            ['changes' => $validated]
        );

        return response()->json([
            'message' => 'Employee remuneration updated successfully',
            'employee_remuneration' => $employeeRemuneration,
        ]);
    }

    public function destroy(EmployeeRemuneration $employeeRemuneration): JsonResponse
    {
        $user = Auth::user();

        if ($employeeRemuneration->business_id !== $user->business_id) {
            abort(403, 'You are not authorized to delete this remuneration');
        }

        $employeeRemuneration->delete();

        ActivityLog::log(
            $user,
            'deleted_employee_remuneration',
            $employeeRemuneration,
            sprintf('Deleted remuneration record ID %s.', $employeeRemuneration->id)
        );

        return response()->json([
            'message' => 'Employee remuneration deleted successfully',
        ]);
    }
}
