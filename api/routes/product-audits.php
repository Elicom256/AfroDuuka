<?php

use App\Http\Controllers\ProductAuditController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [ProductAuditController::class, 'index']);
    Route::post('/', [ProductAuditController::class, 'store']);
    Route::get('/{productAudit}', [ProductAuditController::class, 'show']);
    Route::match(['put', 'patch'], '/{productAudit}', [ProductAuditController::class, 'update']);
    Route::delete('/{productAudit}', [ProductAuditController::class, 'destroy']);
    Route::post('/{productAudit}/approve', [ProductAuditController::class, 'approve']);
    Route::post('/{productAudit}/cancel', [ProductAuditController::class, 'cancel']);
    Route::get('/{productAudit}/report', [ProductAuditController::class, 'report']);
});
