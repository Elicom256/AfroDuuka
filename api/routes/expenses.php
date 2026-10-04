<?php

use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('expense-categories', ExpenseCategoryController::class);

    // Literal segments first: {expense} would otherwise swallow them.
    Route::get('/branch-expenses/monthly-summary', [ExpenseController::class, 'monthlySummary']);
    Route::get('/branch-expenses/totals-by-category', [ExpenseController::class, 'totalsByCategory']);
    Route::post('/branch-expenses/{expense}/approve', [ExpenseController::class, 'approve']);

    Route::apiResource('branch-expenses', ExpenseController::class)
        ->parameters(['branch-expenses' => 'expense']);
});
