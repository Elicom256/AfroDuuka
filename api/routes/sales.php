<?php

use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleItemController;
use Illuminate\Support\Facades\Route;

// Protected user routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource("branch-sales", SaleController::class);
    // Route::apiResource("", SaleController::class);
    // Route::apiResource("item", SaleItemController::class);
    // Route::get("/", [SaleController::class, "index"]);
    // Route::post('/', [SaleController::class, 'store']);
    // Route::get('/{sale}', [SaleController::class, 'show']);
    // Route::put('/{sale}', [SaleController::class, 'update']);
    // Route::delete('/{sale}', [SaleController::class, 'destroy']);
});
