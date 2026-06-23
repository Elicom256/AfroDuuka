<?php

// Finances module - provides cash flow and financial overview
use App\Http\Controllers\CashFlowController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // List all cash flow records as the finances overview (paginated)
    Route::get('/', [CashFlowController::class, 'index']);
    // Show a single cashflow record
    Route::get('/{cashFlow}', [CashFlowController::class, 'show']);
});
