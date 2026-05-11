<?php

use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleItemController;
use Illuminate\Support\Facades\Route;

// Protected user routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get("/", [SaleController::class, "index"]);
    Route::post('/', [SaleItemController::class, 'store']);
    Route::get('/{sale}', [SaleController::class, 'show']);
    Route::put('/{sale}', [SaleController::class, 'update']);
    Route::delete('/{sale}', [SaleController::class, 'destroy']);
});
