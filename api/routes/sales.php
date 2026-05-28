<?php

use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleItemController;
use Illuminate\Support\Facades\Route;

// Protected user routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get("/branch-sales/analytics", [SaleController::class, "salesAnalytics"]);
    Route::apiResource("branch-sales", SaleController::class)->only(["index", "show", "store", "update", "delete"]);
});
