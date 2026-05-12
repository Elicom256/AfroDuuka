<?php

use App\Http\Controllers\PurchaseController;
use Illuminate\Support\Facades\Route;

// Protected user routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get("/", [PurchaseController::class, "index"]);
    Route::post('/', [PurchaseController::class, 'store']);
    Route::get('/{purchase}', [PurchaseController::class, 'show']);
    Route::put('/{purchase}', [PurchaseController::class, 'update']);
    Route::delete('/{purchase}', [PurchaseController::class, 'destroy']);
});
