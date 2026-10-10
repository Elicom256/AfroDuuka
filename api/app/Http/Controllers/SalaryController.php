<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalaryRequest;
use App\Http\Requests\UpdateSalaryRequest;
use App\Models\Salary;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    protected ActivityLogService $activity_log;

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activity_log = $activityLog;
    }

    public function index(): JsonResponse
    {
        $salaries = Salary::with(['role', 'businessBranch', 'setBy'])
            ->latest()
            ->paginate(10);

        // The payroll figure a business actually pays per month. Only active,
        // monthly salaries count: a yearly salary is not part of this month's run,
        // and an inactive salary is not paid at all.
        $monthlyPayroll = Salary::where('status', 'active')
            ->where('period', 'monthly')
            ->sum('amount');

        return response()->json([
            'message' => 'Fetched salaries',
            'salaries' => $salaries,
            'monthly_payroll' => $monthlyPayroll,
            'active_count' => Salary::where('status', 'active')->count(),
        ]);
    }

    public function store(StoreSalaryRequest $request): JsonResponse
    {
        $salary = new Salary([
            ...$request->validated(),
            'set_by' => $request->user()?->id,
        ]);

        // See Salary::$coversAllBranches: an omitted branch_id is stamped with the
        // creator's branch by BaseModel, and the flag is the only way to say
        // "this salary applies to every branch" as a deliberate choice.
        $salary->coversAllBranches = ! $request->filled('business_branch_id');
        $salary->save();

        $this->activity_log->activity(
            'Created Salary',
            "Salary of {$salary->amount} per {$salary->period} set for the {$salary->role?->name} role",
            subject: $salary,
        );

        return response()->json([
            'message' => 'Salary created successfully',
            'salary' => $salary->load(['role', 'businessBranch', 'setBy']),
        ], 201);
    }

    public function show(Salary $salary): JsonResponse
    {
        return response()->json([
            'message' => 'Fetched salary',
            'salary' => $salary->load(['role', 'businessBranch', 'setBy', 'employees']),
        ]);
    }

    public function update(UpdateSalaryRequest $request, Salary $salary): JsonResponse
    {
        $data = $request->validated();

        // `business_branch_id` is nullable, so the UI sends an explicit null to mean
        // "all branches". update() would ignore a null and leave the old branch in
        // place, making that unreachable from the form; forceFill does not.
        if (array_key_exists('business_branch_id', $data) && $data['business_branch_id'] === null) {
            $salary->forceFill(['business_branch_id' => null])->save();
        }

        unset($data['business_branch_id']);

        $salary->update($data);

        $this->activity_log->activity(
            'Updated Salary',
            "Salary record ID {$salary->id} updated",
            ['changes' => $request->validated()],
            $salary,
        );

        return response()->json([
            'message' => 'Salary updated successfully',
            'salary' => $salary->fresh()->load(['role', 'businessBranch', 'setBy']),
        ]);
    }

    public function destroy(Request $request, Salary $salary): JsonResponse
    {
        $salary->delete();

        $this->activity_log->activity(
            'Deleted Salary',
            "Salary record ID {$salary->id} deleted",
            subject: $salary,
        );

        return response()->json([
            'message' => 'Salary deleted successfully',
        ]);
    }
}
