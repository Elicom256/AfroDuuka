<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseCategoryRequest;
use App\Http\Requests\UpdateExpenseCategoryRequest;
use App\Models\ActivityLog;
use App\Models\ExpenseCategory;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ExpenseCategoryController extends Controller
{
    protected ActivityLogService $activity_log;

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activity_log = $activityLog;
    }

    public function index(): JsonResponse
    {
        $categories = ExpenseCategory::with('createdBy')
            ->orderBy('name')
            ->paginate(50);

        return response()->json([
            'message' => 'Fetched expense categories',
            'expense_categories' => $categories,
        ]);
    }

    public function store(StoreExpenseCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $category = ExpenseCategory::create($validated);

        $this->activity_log->activity(
            'Created Expense Category',
            "Expense category '{$category->name}' created"
        );

        return response()->json([
            'message' => 'Expense category created successfully',
            'expense_category' => $category,
        ], 201);
    }

    public function show(ExpenseCategory $expenseCategory): JsonResponse
    {
        $expenseCategory->load('createdBy');

        return response()->json([
            'message' => 'Fetched expense category',
            'expense_category' => $expenseCategory,
        ]);
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        $user = Auth::user();
        if ($expenseCategory->business_id !== $user->business_id) {
            abort(403, 'Unauthorized');
        }

        $expenseCategory->update($request->validated());

        ActivityLog::log(
            $user,
            'updated_expense_category',
            $expenseCategory,
            "Updated expense category ID {$expenseCategory->id}",
            ['changes' => $request->validated()]
        );

        return response()->json([
            'message' => 'Expense category updated successfully',
            'expense_category' => $expenseCategory,
        ]);
    }

    public function destroy(ExpenseCategory $expenseCategory): JsonResponse
    {
        $user = Auth::user();
        if ($expenseCategory->business_id !== $user->business_id) {
            abort(403, 'Unauthorized');
        }

        $expenseCategory->delete();

        ActivityLog::log(
            $user,
            'deleted_expense_category',
            $expenseCategory,
            "Deleted expense category ID {$expenseCategory->id}"
        );

        return response()->json([
            'message' => 'Expense category deleted successfully',
        ]);
    }
}
