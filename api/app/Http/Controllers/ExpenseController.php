<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\ActivityLog;
use App\Models\Expense;
use App\Services\ActivityLogService;
use App\Services\CashFlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    protected ActivityLogService $activity_log;
    protected CashFlowService $cashFlowService;

    public function __construct(ActivityLogService $activityLog, CashFlowService $cashFlowService)
    {
        $this->activity_log = $activityLog;
        $this->cashFlowService = $cashFlowService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Expense::with(['category', 'businessBranch', 'createdBy']);

        if ($request->filled('expense_category_id')) {
            $query->where('expense_category_id', $request->expense_category_id);
        }

        if ($request->filled('business_branch_id')) {
            $query->where('business_branch_id', $request->business_branch_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%");
            });
        }

        $totalAmount = (clone $query)->sum('amount');

        $expenses = $query->orderByDesc('payment_date')->paginate(10);

        return response()->json([
            'message' => 'Fetched expenses',
            'expenses' => $expenses,
            'total_amount' => $totalAmount,
        ]);
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $expense = Expense::create($validated);

        $this->cashFlowService->createCashFlowForExpense($expense, (float) $validated['amount']);

        $this->activity_log->activity(
            'Recorded Expense',
            "Expense of {$validated['amount']} recorded"
        );

        return response()->json([
            'message' => 'Expense created successfully',
            'expense' => $expense,
        ], 201);
    }

    public function show(Expense $expense): JsonResponse
    {
        $expense->load(['category', 'businessBranch', 'createdBy']);

        return response()->json([
            'message' => 'Fetched expense',
            'expense' => $expense,
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): JsonResponse
    {
        $expense->update($request->validated());

        ActivityLog::log(
            $request->user(),
            'updated_expense',
            $expense,
            "Updated expense ID {$expense->id}",
            ['changes' => $request->validated()]
        );

        return response()->json([
            'message' => 'Expense updated successfully',
            'expense' => $expense,
        ]);
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $expense->delete();

        ActivityLog::log(
            $request->user(),
            'deleted_expense',
            $expense,
            "Deleted expense ID {$expense->id}"
        );

        return response()->json([
            'message' => 'Expense deleted successfully',
        ]);
    }

    public function approve(Expense $expense): JsonResponse
    {
        $expense->update(['status' => 'approved']);

        ActivityLog::log(
            $request->user(),
            'approved_expense',
            $expense,
            "Approved expense ID {$expense->id}"
        );

        return response()->json([
            'message' => 'Expense approved successfully',
            'expense' => $expense,
        ]);
    }

    public function monthlySummary(Request $request): JsonResponse
    {
        $year = $request->input('year', now()->year);

        $expenses = Expense::selectRaw("
                DATE_FORMAT(payment_date, '%Y-%m') as month,
                SUM(amount) as total,
                COUNT(*) as count
            ")
            ->whereYear('payment_date', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'message' => 'Fetched monthly expense summary',
            'monthly_summary' => $expenses,
        ]);
    }

    public function totalsByCategory(Request $request): JsonResponse
    {
        $year = $request->input('year', now()->year);

        $totals = Expense::selectRaw("
                expense_category_id,
                SUM(amount) as total,
                COUNT(*) as count
            ")
            ->with('category')
            ->whereYear('payment_date', $year)
            ->groupBy('expense_category_id')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'message' => 'Fetched expense totals by category',
            'totals_by_category' => $totals,
        ]);
    }
}
