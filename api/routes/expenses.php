<?php

use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Route::get('/categories', [ExpenseCategoryController::class, 'index']);
    // Route::post('/categories', [ExpenseCategoryController::class, 'store']);
    // Route::get('/categories/{expenseCategory}', [ExpenseCategoryController::class, 'show']);
    // Route::put('/categories/{expenseCategory}', [ExpenseCategoryController::class, 'update']);
    // Route::delete('/categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy']);

    // Route::get('/', [ExpenseController::class, 'index']);
    // Route::post('/', [ExpenseController::class, 'store']);
    // Route::get('/monthly-summary', [ExpenseController::class, 'monthlySummary']);
    // Route::get('/totals-by-category', [ExpenseController::class, 'totalsByCategory']);
    // Route::post('/{expense}/approve', [ExpenseController::class, 'approve']);
    // Route::get('/{expense}', [ExpenseController::class, 'show']);
    // Route::put('/{expense}', [ExpenseController::class, 'update']);
    // Route::delete('/{expense}', [ExpenseController::class, 'destroy']);

    Route::apiResource("expense-categories", ExpenseCategoryController::class);
    Route::apiResource("branch-expenses", ExpenseController::class);
});
