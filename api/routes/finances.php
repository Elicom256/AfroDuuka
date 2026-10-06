<?php

use App\Http\Controllers\BusinessCreditController;
use App\Http\Controllers\BusinessDebitController;
use App\Http\Controllers\CashDrawerController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\CustomerCreditController;
use App\Http\Controllers\FinanceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Finance Dashboard
    Route::get('dashboard', [FinanceController::class, 'dashboard']);

    // Financial Transactions
    Route::get('transactions', [FinanceController::class, 'transactions']);
    Route::get('transactions/{id}', [FinanceController::class, 'transaction']);

    Route::post('cash-drawers/open', [CashDrawerController::class, 'open']);
    Route::post('cash-drawers/{session}/close', [CashDrawerController::class, 'close']);
    Route::get('cash-drawers/{session}', [CashDrawerController::class, 'show']);
    Route::get('customers/{customer}/credit-balance', [CustomerCreditController::class, 'balance']);
    Route::post('customers/{customer}/credit-payments', [CustomerCreditController::class, 'payment']);

    Route::get('business-debits/overdue', [BusinessDebitController::class, 'overdue']);
    Route::post('business-debits/{businessDebit}/pay', [BusinessDebitController::class, 'pay']);
    Route::resource('business-debits', BusinessDebitController::class)->except(['create', 'edit']);

    Route::get('business-credits/overdue', [BusinessCreditController::class, 'overdue']);
    Route::resource('business-credits', BusinessCreditController::class)->except(['create', 'edit']);

    // Manual Adjustments (admin only)
    Route::post('adjustments', [FinanceController::class, 'adjustment']);

    // Repair an adjustment written before a direction became required. Same role gate as
    // creating one: it writes the same column. Declared before the '/{cashFlow}' route
    // below so 'adjustments' is never read as a cashFlow id.
    Route::patch('adjustments/{cashFlow}/direction', [CashFlowController::class, 'setDirection']);

    // Financial Reports
    Route::prefix('reports')->group(function () {
        Route::get('revenue', [FinanceController::class, 'revenueReport']);
        Route::get('expenses', [FinanceController::class, 'expenseReport']);
        Route::get('income-summary', [FinanceController::class, 'incomeSummary']);
        Route::get('branch-statement/{branchId}', [FinanceController::class, 'branchStatement']);
        Route::get('business-statement', [FinanceController::class, 'businessStatement']);
    });

    // Cash flow ledger. Writes are restricted to manual adjustments by the requests
    // themselves; destroy is deliberately not routed, because deleting a ledger row can
    // strand the sale or purchase it documents and the CHECK constraint has no answer to
    // that. Reversing stock on deletion is an open schema decision, not a route.
    Route::get('/', [CashFlowController::class, 'index']);
    Route::post('/', [CashFlowController::class, 'store']);
    Route::get('/{cashFlow}', [CashFlowController::class, 'show']);
    Route::patch('/{cashFlow}', [CashFlowController::class, 'update']);
});
