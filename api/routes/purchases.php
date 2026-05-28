<?php

use App\Http\Controllers\PurchaseController;
use Illuminate\Support\Facades\Route;

// Protected user routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get("/branch-purchases/analytics", [PurchaseController::class, "salesAnalytics"]);
    Route::apiResource("branch-purchases", PurchaseController::class)->only(["index", "show", "store", "update", "delete"]);
});
