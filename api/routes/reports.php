<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Reports\BranchPerformanceReports;
use App\Http\Controllers\Reports\StockSummaryReports;
use App\Http\Controllers\Reports\LowStockReports;
use App\Http\Controllers\Reports\OutOfStockReports;
use App\Http\Controllers\Reports\DeadStockReports;
use App\Http\Controllers\Reports\InventoryValuationReports;
use App\Http\Controllers\Reports\SalesByProductReports;
use App\Http\Controllers\Reports\StockMovementReports;
use App\Models\CoreSettings\ReportsSettings;

Route::middleware('auth:sanctum')->group(function () {

   // to register a middleware to check if repports are enabled

    Route::get('/branch-performance', [BranchPerformanceReports::class, 'index']);
    Route::get('/stock-summary', [StockSummaryReports::class, 'index']);
    Route::get('/low-stock', [LowStockReports::class, 'index']);
    Route::get('/out-of-stock', [OutOfStockReports::class, 'index']);
    Route::get('/dead-stock', [DeadStockReports::class, 'index']);
    Route::get('/inventory-valuation', [InventoryValuationReports::class, 'index']);
    Route::get('/sales-by-product', [SalesByProductReports::class, 'index']);
    Route::get('/stock-movement', [StockMovementReports::class, 'index']);
});
