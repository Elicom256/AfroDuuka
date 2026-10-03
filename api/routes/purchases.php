<?php

use App\Http\Controllers\PurchaseController;
use Illuminate\Support\Facades\Route;

// Protected user routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get("/branch-purchases/analytics", [PurchaseController::class, "salesAnalytics"]);
    Route::post('/branch-purchases/{purchase}/receive', [PurchaseController::class, 'receive']);
    Route::apiResource("branch-purchases", PurchaseController::class)->only(["index", "show", "store", "update", "destroy"])->parameters(["branch-purchases" => "purchase"]);
});
