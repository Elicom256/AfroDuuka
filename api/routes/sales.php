<?php

use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

// Protected user routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/branch-sales/analytics', [SaleController::class, 'salesAnalytics']);
    Route::get('/branch-sales/return-analytics', [SaleController::class, 'returnAnalytics']);
    Route::apiResource('branch-sales', SaleController::class)->only(['index', 'show', 'store', 'update'])->parameters(['branch-sales' => 'sale']);
});
