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
use Illuminate\Support\Facades\Auth;

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
        $user = Auth::user();
        $query = Expense::with(['category', 'businessBranch', 'createdBy'])
            ->where('business_id', $user->business_id);

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
        $user = Auth::user();
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
        $user = Auth::user();
        if ($expense->business_id !== $user->business_id) {
            abort(403, 'Unauthorized');
        }

        $expense->load(['category', 'businessBranch', 'createdBy']);

        return response()->json([
            'message' => 'Fetched expense',
            'expense' => $expense,
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): JsonResponse
    {
        $user = Auth::user();
        if ($expense->business_id !== $user->business_id) {
            abort(403, 'Unauthorized');
        }

        $expense->update($request->validated());

        ActivityLog::log(
            $user,
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
        $user = Auth::user();
        if ($expense->business_id !== $user->business_id) {
            abort(403, 'Unauthorized');
        }

        $expense->delete();

        ActivityLog::log(
            $user,
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
        $user = Auth::user();
        if ($expense->business_id !== $user->business_id) {
            abort(403, 'Unauthorized');
        }

        $expense->update(['status' => 'approved']);

        ActivityLog::log(
            $user,
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
        $user = Auth::user();
        $year = $request->input('year', now()->year);

        $expenses = Expense::selectRaw("
                DATE_FORMAT(payment_date, '%Y-%m') as month,
                SUM(amount) as total,
                COUNT(*) as count
            ")
            ->where('business_id', $user->business_id)
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
        $user = Auth::user();
        $year = $request->input('year', now()->year);

        $totals = Expense::selectRaw("
                expense_category_id,
                SUM(amount) as total,
                COUNT(*) as count
            ")
            ->with('category')
            ->where('business_id', $user->business_id)
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
