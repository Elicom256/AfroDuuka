<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Services\ActivityLogService;
use App\Services\CashFlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $this->authorize('viewAny', Expense::class);
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
        $this->authorize('create', Expense::class);
        $validated = $request->validated();

        // The expense and the cash flow row that documents it are one fact. Written
        // separately, a failure on the second left an expense with no ledger entry —
        // which the cash-flow reports then silently omit, so the month's expenses and
        // the month's cash flow disagreed with no error anywhere.
        $expense = DB::transaction(function () use ($validated) {
            $expense = Expense::create($validated);

            $this->cashFlowService->createCashFlowForExpense($expense, (float) $validated['amount']);

            return $expense;
        });

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
        $this->authorize('view', $expense);
        $expense->load(['category', 'businessBranch', 'createdBy']);

        return response()->json([
            'message' => 'Fetched expense',
            'expense' => $expense,
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): JsonResponse
    {
        $this->authorize('update', $expense);
        $validated = $request->validated();

        // The amount and date live in two rows that have to agree — the cash-flow
        // reports read the ledger, not the expense. A failure between the two writes
        // left an expense restated without its ledger entry, so the two screens
        // showed different figures for the same spend.
        DB::transaction(function () use ($expense, $validated) {
            $expense->update($validated);

            $cashFlow = $expense->cashFlow;
            if ($cashFlow) {
                $cashFlow->update([
                    'amount' => $validated['amount'] ?? $expense->amount,
                    'transaction_date' => $validated['payment_date'] ?? $expense->payment_date?->toDateString(),
                ]);
            }
        });

        $this->activity_log->activity(
            'updated_expense',
            "Updated expense ID {$expense->id}",
            ['changes' => $validated],
            $expense,
        );

        return response()->json([
            'message' => 'Expense updated successfully',
            'expense' => $expense,
        ]);
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $this->authorize('delete', $expense);

        abort(405, 'Expenses cannot be deleted once recorded. Record a reversing entry instead.');
    }

    public function approve(Expense $expense): JsonResponse
    {
        $this->authorize('approve', $expense);
        $expense->update(['status' => 'approved']);

        $this->activity_log->activity(
            'approved_expense',
            "Approved expense ID {$expense->id}",
            subject: $expense,
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

        $totals = Expense::selectRaw('
                expense_category_id,
                SUM(amount) as total,
                COUNT(*) as count
            ')
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
