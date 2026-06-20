<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeSalaryRequest;
use App\Http\Requests\UpdateEmployeeSalaryRequest;
use App\Models\EmployeeSalary;
use App\Models\Worker;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EmployeeSalaryController extends Controller
{
    protected ActivityLogService $activity_log;

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activity_log = $activityLog;
    }

    public function index(): JsonResponse
    {
        $user = Auth::user();

        $salaries = EmployeeSalary::with(['worker.user.businessBranch'])
            ->whereHas('worker.user', function ($query) use ($user) {
                $query->where('business_id', $user->business_id);
            })
            ->orderByDesc('effective_date')
            ->paginate(10);

        $totalMonthly = EmployeeSalary::whereHas('worker.user', function ($query) use ($user) {
            $query->where('business_id', $user->business_id);
        })->where('status', 'active')->sum('amount');

        $activeCount = EmployeeSalary::whereHas('worker.user', function ($query) use ($user) {
            $query->where('business_id', $user->business_id);
        })->where('status', 'active')->count();

        return response()->json([
            'message' => 'Fetched employee salaries',
            'employee_salaries' => $salaries,
            'totalMonthly' => $totalMonthly,
            'activeCount' => $activeCount,
        ]);
    }

    public function store(StoreEmployeeSalaryRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        $worker = Worker::with('user')->findOrFail($validated['worker_id']);
        if ($worker->user->business_id !== $user->business_id) {
            abort(403, 'Worker does not belong to your business');
        }

        $salary = EmployeeSalary::create($validated);
        $this->activity_log->activity(
            'Recorded Employee Salary',
            "Salary of {$validated['amount']} {$validated['currency']} set for {$worker->user->name}"
        );

        return response()->json([
            'message' => 'Employee salary created successfully',
            'employee_salary' => $salary->load('worker.user'),
        ], 201);
    }

    public function show(EmployeeSalary $employeeSalary): JsonResponse
    {
        $employeeSalary->load(['worker.user', 'worker.businessBranch']);

        return response()->json([
            'message' => 'Fetched employee salary',
            'employee_salary' => $employeeSalary,
        ]);
    }

    public function update(UpdateEmployeeSalaryRequest $request, EmployeeSalary $employeeSalary): JsonResponse
    {
        $user = Auth::user();

        if ($employeeSalary->worker->user->business_id !== $user->business_id) {
            abort(403, 'You are not authorized to update this salary');
        }

        $employeeSalary->update($request->validated());

        $this->activity_log->activity(
            'Updated Employee Salary',
            "Salary record ID {$employeeSalary->id} updated"
        );

        return response()->json([
            'message' => 'Employee salary updated successfully',
            'employee_salary' => $employeeSalary->fresh()->load('worker.user'),
        ]);
    }

    public function destroy(EmployeeSalary $employeeSalary): JsonResponse
    {
        $user = Auth::user();

        if ($employeeSalary->worker->user->business_id !== $user->business_id) {
            abort(403, 'You are not authorized to delete this salary');
        }

        $employeeSalary->delete();

        $this->activity_log->activity(
            'Deleted Employee Salary',
            "Salary record ID {$employeeSalary->id} deleted"
        );

        return response()->json([
            'message' => 'Employee salary deleted successfully',
        ]);
    }
}
