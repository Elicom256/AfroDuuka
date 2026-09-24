<?php

use App\Http\Controllers\SaleOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get("/", [SaleOrderController::class, "index"]);
    Route::post("/", [SaleOrderController::class, "store"]);
    Route::get("/{sale_order}", [SaleOrderController::class, "show"]);
    Route::put("/{sale_order}", [SaleOrderController::class, "update"]);
    Route::delete("/{sale_order}", [SaleOrderController::class, "destroy"]);
});