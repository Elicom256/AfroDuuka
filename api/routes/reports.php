<?php

use App\Http\Controllers\Reports\BranchPerformanceReports;
use App\Http\Controllers\Reports\DeadStockReports;
use App\Http\Controllers\Reports\InventoryValuationReports;
use App\Http\Controllers\Reports\LowStockReports;
use App\Http\Controllers\Reports\MonthlyPerformanceReport;
use App\Http\Controllers\Reports\OutOfStockReports;
use App\Http\Controllers\Reports\SalesByProductReports;
use App\Http\Controllers\Reports\StockMovementReports;
use App\Http\Controllers\Reports\StockSummaryReports;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    // to register a middleware to check if repports are enabled

    Route::get('/branch-performance', [BranchPerformanceReports::class, 'index']);

    // The same monthly document the notification email attaches, downloadable on demand.
    Route::get('/monthly-performance', [MonthlyPerformanceReport::class, 'index']);
    Route::get('/monthly-performance/pdf', [MonthlyPerformanceReport::class, 'pdf']);
    Route::get('/stock-summary', [StockSummaryReports::class, 'index']);
    Route::get('/low-stock', [LowStockReports::class, 'index']);
    Route::get('/out-of-stock', [OutOfStockReports::class, 'index']);
    Route::get('/dead-stock', [DeadStockReports::class, 'index']);
    Route::get('/inventory-valuation', [InventoryValuationReports::class, 'index']);
    Route::get('/sales-by-product', [SalesByProductReports::class, 'index']);
    Route::get('/stock-movement', [StockMovementReports::class, 'index']);
});
