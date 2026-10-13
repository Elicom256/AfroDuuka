<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalaryRequest;
use App\Http\Requests\UpdateSalaryRequest;
use App\Models\Salary;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryController extends Controller
{
    protected ActivityLogService $activity_log;

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activity_log = $activityLog;
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Salary::class);
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
        $this->authorize('create', Salary::class);

        // The salary row and the activity_log entry recording who set it are two
        // writes. Payroll is sensitive enough that a salary with no record of who
        // approved it is worse than no salary at all, so they commit together.
        $salary = DB::transaction(function () use ($request) {
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

            return $salary;
        });

        return response()->json([
            'message' => 'Salary created successfully',
            'salary' => $salary->load(['role', 'businessBranch', 'setBy']),
        ], 201);
    }

    public function show(Salary $salary): JsonResponse
    {
        $this->authorize('view', $salary);

        return response()->json([
            'message' => 'Fetched salary',
            'salary' => $salary->load(['role', 'businessBranch', 'setBy', 'employees']),
        ]);
    }

    public function update(UpdateSalaryRequest $request, Salary $salary): JsonResponse
    {
        $this->authorize('update', $salary);
        $data = $request->validated();

        // Two separate writes to the same row: forceFill()->save() to clear the
        // branch, then update() for the rest. A failure between them left a salary
        // that had silently stopped applying to one branch while still showing the
        // old amount — a payroll figure nobody could reconstruct. One save does both.
        DB::transaction(function () use ($salary, $data, $request) {
            // `business_branch_id` is nullable, so the UI sends an explicit null to mean
            // "all branches". update() would ignore a null and leave the old branch in
            // place, making that unreachable from the form; forceFill does not.
            if (array_key_exists('business_branch_id', $data) && $data['business_branch_id'] === null) {
                $salary->forceFill(['business_branch_id' => null]);
            }

            unset($data['business_branch_id']);

            $salary->update($data);

            $this->activity_log->activity(
                'Updated Salary',
                "Salary record ID {$salary->id} updated",
                ['changes' => $request->validated()],
                $salary,
            );
        });

        return response()->json([
            'message' => 'Salary updated successfully',
            'salary' => $salary->fresh()->load(['role', 'businessBranch', 'setBy']),
        ]);
    }

    public function destroy(Request $request, Salary $salary): JsonResponse
    {
        $this->authorize('delete', $salary);

        abort(405, 'Salaries cannot be deleted once recorded. Record a reversing adjustment instead.');
    }
}
