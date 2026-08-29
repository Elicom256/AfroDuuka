<?php

use App\Http\Controllers\FinancialAuditController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [FinancialAuditController::class, 'index']);
    Route::post('/', [FinancialAuditController::class, 'store']);
    Route::get('/{financialAudit}', [FinancialAuditController::class, 'show']);
    Route::match(['put', 'patch'], '/{financialAudit}', [FinancialAuditController::class, 'update']);
    Route::delete('/{financialAudit}', [FinancialAuditController::class, 'destroy']);
    Route::post('/{financialAudit}/approve', [FinancialAuditController::class, 'approve']);
    Route::post('/{financialAudit}/cancel', [FinancialAuditController::class, 'cancel']);
    Route::get('/{financialAudit}/report', [FinancialAuditController::class, 'report']);
});
