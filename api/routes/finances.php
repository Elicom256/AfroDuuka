<?php

use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\FinanceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Finance Dashboard
    Route::get('dashboard', [FinanceController::class, 'dashboard']);

    // Financial Transactions
    Route::get('transactions', [FinanceController::class, 'transactions']);
    Route::get('transactions/{id}', [FinanceController::class, 'transaction']);

    // Manual Adjustments (admin only)
    Route::post('adjustments', [FinanceController::class, 'adjustment']);

    // Financial Reports
    Route::prefix('reports')->group(function () {
        Route::get('revenue', [FinanceController::class, 'revenueReport']);
        Route::get('expenses', [FinanceController::class, 'expenseReport']);
        Route::get('income-summary', [FinanceController::class, 'incomeSummary']);
        Route::get('branch-statement/{branchId}', [FinanceController::class, 'branchStatement']);
        Route::get('business-statement', [FinanceController::class, 'businessStatement']);
    });

    // Keep existing cash flow routes
    Route::get('/', [CashFlowController::class, 'index']);
    Route::get('/{cashFlow}', [CashFlowController::class, 'show']);
});
